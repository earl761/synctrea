<?php

namespace App\Services\Api;

use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class IngramMicroService
{
    protected Supplier $supplier;
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected const TOKEN_CACHE_KEY = 'ingram_micro_access_token';
    protected const TOKEN_CACHE_TTL = 3500; // slightly less than 1 hour to ensure token refresh

    public function __construct()
    {
        $this->supplier = Supplier::where('type', 'ingram_micro')
            ->where('is_active', true)
            ->firstOrFail();

        $this->baseUrl = rtrim($this->supplier->api_endpoint, '/');
        $this->clientId = decrypt($this->supplier->api_key);
        $this->clientSecret = decrypt($this->supplier->api_secret);
    }

    /**
     * Get the access token, either from cache or by requesting a new one
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function () {
            return $this->requestNewAccessToken();
        });
    }

    /**
     * Request a new access token from the Ingram Micro API
     *
     * @return string|null
     */
    protected function requestNewAccessToken(): ?string
    {
        try {
            $response = Http::asForm()->post($this->baseUrl . '/oauth/oauth30/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => 'basic'
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['access_token'])) {
                Log::info('Successfully obtained new Ingram Micro API access token');
                return $responseData['access_token'];
            }

            Log::error('Failed to obtain Ingram Micro API access token', [
                'status' => $response->status(),
                'response' => $responseData
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error requesting Ingram Micro API access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Make an authenticated request to the Ingram Micro API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|null
     */
    public function makeRequest(string $method, string $endpoint, array $data = []): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('Unable to make Ingram Micro API request: No access token available');
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->{strtolower($method)}($this->baseUrl . '/' . ltrim($endpoint, '/'), $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Ingram Micro API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error making Ingram Micro API request', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}