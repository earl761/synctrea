<?php

namespace App\Services\Api;

use App\Models\ConnectionPair;
use App\Models\Destination;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use Aws\Sts\StsClient;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AmazonSpApiSigner
{
    private Destination $dest;

    // pulled from $dest->credentials JSON/array
    private string $lwaClientId;
    private string $lwaClientSecret;
    private string $lwaRefreshToken;
    private string $roleArn;
    private string $awsKey;
    private string $awsSecret;
    private string $region;
    private bool   $sandbox;

    private const LWA_URL = 'https://api.amazon.com/auth/o2/token';

    /**
     * Create a signer for the Amazon destination that belongs to the
     * supplied ConnectionPair.
     *
     * @throws ModelNotFoundException if destination missing or not Amazon.
     * @throws \InvalidArgumentException if required credential keys are absent.
     */
    public function __construct(ConnectionPair $connection)
    {
        $destination = $connection->destination;

        if (!$destination) {
            throw new ModelNotFoundException('No destination found for the connection pair.');
        }

        if ($destination->type !== Destination::TYPE_AMAZON) {
            throw new ModelNotFoundException(
                'ConnectionPair does not reference an Amazon destination. Found type: ' . $destination->type
            );
        }

        $this->dest = $destination;
        Log::debug('AmazonSpApiSigner init', ['destination_id' => $this->dest->id]);

        // credentials stored as encrypted JSON or cast array
        $creds = is_array($this->dest->credentials)
               ? $this->dest->credentials
               : json_decode($this->dest->credentials, true) ?? [];

        Log::debug('AmazonSpApiSigner init', ['destination_id' => $this->dest->id]);

        foreach (['client_id', 'client_secret', 'refresh_token',
                  'role_arn', 'aws_key', 'aws_secret'] as $k) {
            if (empty($creds[$k])) {
                throw new \InvalidArgumentException("Missing '{$k}' in destination credentials.");
            }
        }

        $this->lwaClientId     = $creds['client_id'];
        $this->lwaClientSecret = $creds['client_secret'];
        $this->lwaRefreshToken = $creds['refresh_token'];
        $this->roleArn         = $creds['role_arn'];
        $this->awsKey          = $creds['aws_key'];
        $this->awsSecret       = $creds['aws_secret'];
        $this->region          = $creds['region'] ?? 'us-east-1';
        $this->sandbox         = (bool)($creds['sandbox'] ?? false);
    }

    /* ───────────────────────── public API ───────────────────────── */

    public function sign(string $method, string $path, array $query = [], $body = null): object
    {
        $endpoint = sprintf(
            'https://%ssellingpartnerapi-na.amazon.com',
            $this->sandbox ? 'sandbox.' : ''
        );

        $url = $endpoint . $path . ($query ? '?' . http_build_query($query) : '');

        $headers = [
            'host'               => parse_url($endpoint, PHP_URL_HOST),
            'user-agent'         => 'alterity/1.0 (Language=PHP)',
            'x-amz-access-token' => $this->getLwaToken(),
            'content-type'       => 'application/json; charset=UTF-8',
        ];

        $payload = $body === null
            ? ''
            : (is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE));

        $request = new Request($method, $url, $headers, $payload);

        $signer = new SignatureV4('execute-api', $this->region);
        $creds  = $this->getTempCreds();

        $signed = $signer->signRequest(
            $request,
            new Credentials(
                $creds['AccessKeyId'],
                $creds['SecretAccessKey'],
                $creds['SessionToken']
            )
        );

        return (object) [
            'url'     => (string) $signed->getUri(),
            'headers' => $signed->getHeaders(),
            'query'   => $query,
        ];
    }

    /* ───────────── internal helpers ───────────── */

    private function cache(string $suffix): string
    {
        return "spapi:{$this->dest->id}:{$suffix}";
    }

    private function getLwaToken(): string
    {
        if ($tok = Cache::get($this->cache('lwa'))) {
            return $tok;
        }

        $resp = Http::asForm()->post(self::LWA_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->lwaRefreshToken,
            'client_id'     => $this->lwaClientId,
            'client_secret' => $this->lwaClientSecret,
        ]);

        throw_if(!$resp->ok(), new \RuntimeException('LWA token error: '.$resp->body()));

        $token  = $resp->json('access_token');
        $expiry = now()->addSeconds($resp->json('expires_in') - 30);
        Cache::put($this->cache('lwa'), $token, $expiry);

        return $token;
    }

    private function getTempCreds(): array
    {
        if ($c = Cache::get($this->cache('aws'))) {
            return $c;
        }

        $sts = new StsClient([
            'region'      => $this->region,
            'version'     => '2011-06-15',
            'credentials' => [
                'key'    => $this->awsKey,
                'secret' => $this->awsSecret,
            ],
        ]);

        $res   = $sts->assumeRole([
            'RoleArn'         => $this->roleArn,
            'RoleSessionName' => 'spapi-' . Str::random(6),
            'DurationSeconds' => 3600,
        ]);

        $creds  = $res->get('Credentials')->toArray();
        $expiry = $creds['Expiration']->getTimestamp() - time() - 30;
        Cache::put($this->cache('aws'), $creds, $expiry);

        return $creds;
    }
}
