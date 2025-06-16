<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Models\Product;
use App\Models\ConnectionPair;
use App\Services\Api\Traits\HandlesRateLimiting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;

class AmazonApiClient
{
    use HandlesRateLimiting;

    private Destination $destination;
    private ConnectionPair $connectionPair;
    private string $region;
    private bool $sandbox;
    private array $credentials;

    private const SELLER_ID = 'A3S94RDJXZL1YY';
    private const MARKETPLACE_ID = 'A2EUQ1WTGCTBG2';
    private const CATALOG_API_VERSION = '2022-04-01';
    private const LISTINGS_API_VERSION = '2021-08-01';
    private const FEEDS_API_VERSION = '2021-06-30';
    private const VALID_FEED_TYPES = [
        'POST_STD_ACES_DATA',
        'POST_FLAT_FILE_BOOKLOADER_DATA',
        'POST_FLAT_FILE_INVLOADER_DATA',
        'POST_FLAT_FILE_LISTINGS_DATA',
        'POST_FLAT_FILE_CONVERGENCE_LISTINGS_DATA',
        'POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA',
        'POST_INVENTORY_AVAILABILITY_DATA',
        'JSON_LISTINGS_FEED',
        'POST_PRODUCT_OVERRIDES_DATA',
        'POST_PRODUCT_PRICING_DATA',
        'POST_PRODUCT_DATA',
        'POST_PRODUCT_IMAGE_DATA',
        'POST_PRODUCT_RELATIONSHIP_DATA',
        'POST_UIEE_BOOKLOADER_DATA'
    ];

    private const ENDPOINTS = [
        'US' => [
            'prod' => 'https://sellingpartnerapi-na.amazon.com',
            'sandbox' => 'https://sandbox.sellingpartnerapi-na.amazon.com'
        ],
        'CA' => [
            'prod' => 'https://sellingpartnerapi-na.amazon.com',
            'sandbox' => 'https://sandbox.sellingpartnerapi-na.amazon.com'
        ],
        'EU' => [
            'prod' => 'https://sellingpartnerapi-eu.amazon.com',
            'sandbox' => 'https://sandbox.sellingpartnerapi-eu.amazon.com'
        ],
        'FE' => [
            'prod' => 'https://sellingpartnerapi-fe.amazon.com',
            'sandbox' => 'https://sandbox.sellingpartnerapi-fe.amazon.com'
        ]
    ];

    public function __construct(ConnectionPair $connectionPair)
    {
        $this->connectionPair = $connectionPair;

        if (!$connectionPair->destination) {
            Log::error('ConnectionPair has no associated Destination', [
                'connection_pair_id' => $connectionPair->id,
                'destination_id' => $connectionPair->destination_id
            ]);
            throw new \InvalidArgumentException('ConnectionPair must have a valid Destination');
        }

        $this->destination = $connectionPair->destination;

        if ($this->destination->type !== 'amazon') {
            throw new \InvalidArgumentException('Destination must be of type amazon');
        }

        // Validate and sanitize destination fields
        $this->region = $this->cleanUtf8String($this->destination->region );
        if (!$this->isValidUtf8($this->region)) {
            Log::error('Invalid UTF-8 in destination region', [
                'region' => $this->region,
                'connection_pair_id' => $connectionPair->id
            ]);
            throw new \InvalidArgumentException('Destination region contains invalid UTF-8 characters');
        }
        if (!isset(self::ENDPOINTS[$this->region])) {
            throw new \InvalidArgumentException("Unsupported region: {$this->region}. Supported regions: " . implode(', ', array_keys(self::ENDPOINTS)));
        }

        $this->credentials = $this->destination->credentials ?? [];
        if (isset($this->credentials['refresh_token'])) {
            $this->credentials['refresh_token'] = $this->cleanUtf8String($this->credentials['refresh_token']);
            if (!$this->isValidUtf8($this->credentials['refresh_token'])) {
                Log::error('Invalid UTF-8 in refresh_token', [
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                throw new \InvalidArgumentException('Refresh token contains invalid UTF-8 characters');
            }
        }

        if ($this->destination->api_key) {
            $this->destination->api_key = $this->cleanUtf8String($this->destination->api_key);
            if (!$this->isValidUtf8($this->destination->api_key)) {
                Log::error('Invalid UTF-8 in api_key', [
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                throw new \InvalidArgumentException('API key contains invalid UTF-8 characters');
            }
        }

        if ($this->destination->api_secret) {
            $this->destination->api_secret = $this->cleanUtf8String($this->destination->api_secret);
            if (!$this->isValidUtf8($this->destination->api_secret)) {
                Log::error('Invalid UTF-8 in api_secret', [
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                throw new \InvalidArgumentException('API secret contains invalid UTF-8 characters');
            }
        }

        $this->sandbox = (bool)($this->cleanUtf8String($this->connectionPair->settings['sandbox'] ?? '0') === '1');
    }

    private function getBaseUrl(): string
    {
        return self::ENDPOINTS[$this->region][$this->sandbox ? 'sandbox' : 'prod'];
    }

    private function getAccessToken(): string
    {
        $response = Http::asForm()->post('https://api.amazon.com/auth/o2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->credentials['refresh_token'],
            'client_id' => $this->destination->api_key,
            'client_secret' => $this->destination->api_secret,
        ]);

        if ($response->failed()) {
            Log::error('Failed to get LWA access token', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to get LWA access token: ' . $response->body());
        }

        $accessToken = $response->json('access_token');
        if (!$this->isValidUtf8($accessToken)) {
            Log::error('Invalid UTF-8 in access token', [
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Access token contains invalid UTF-8 characters');
        }
        return $accessToken;
    }

   

    private function signRequest(string $method, string $path, array $query = [], $body = null): array
    {
        $url = $this->getBaseUrl() . $path . ($query ? '?' . http_build_query($query) : '');

        $headers = [
            'host' => parse_url($this->getBaseUrl(), PHP_URL_HOST),
            'user-agent' => 'alterity/1.0 (Language=PHP)',
            'x-amz-access-token' => $this->getAccessToken(),
            'content-type' => 'application/json; charset=UTF-8',
        ];

        $payload = $body === null ? '' : (is_string($body) ? $body : $this->sanitizeArrayForJson($body));
        Log::debug('Payload before JSON encoding', [
            'method' => $method,
            'path' => $path,
            'payload' => $payload,
            'connection_pair_id' => $this->connectionPair->id
        ]);

        $request = new Request($method, $url, $headers, is_string($payload) ? $payload : json_encode($payload, JSON_THROW_ON_ERROR));

        $signer = new SignatureV4('execute-api', $this->region);
        $creds = new Credentials(
            $this->destination->api_key,
            $this->destination->api_secret
        );

        $signed = $signer->signRequest($request, $creds);

        Log::debug('Signed request headers', [
            'method' => $method,
            'path' => $path,
            'headers' => $signed->getHeaders(),
            'connection_pair_id' => $this->connectionPair->id
        ]);

        return [
            'url' => (string) $signed->getUri(),
            'headers' => $signed->getHeaders(),
        ];
    }

    private function getMarketplaceId(): string
    {
        return self::MARKETPLACE_ID;
    }

    private function cleanUtf8String(string $input): string
    {
        // Convert to UTF-8, remove invalid sequences
        $cleaned = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        // Remove control characters, preserve dots, hyphens, and alphanumeric
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $cleaned);
        return $cleaned ?: '';
    }

    private function isValidUtf8(string $input): bool
    {
        return mb_check_encoding($input, 'UTF-8') && json_encode($input) !== false;
    }

    private function sanitizeArrayForJson($data)
    {
        if (is_array($data)) {
            return array_map(function ($value) {
                if (is_array($value)) {
                    return $this->sanitizeArrayForJson($value);
                }
                if (is_string($value)) {
                    $cleaned = $this->cleanUtf8String($value);
                    if (!$this->isValidUtf8($cleaned)) {
                        Log::warning('Invalid UTF-8 string after cleaning', [
                            'original' => $value,
                            'cleaned' => $cleaned,
                            'connection_pair_id' => $this->connectionPair->id
                        ]);
                        return '';
                    }
                    return $cleaned;
                }
                return $value;
            }, $data);
        }
        if (is_string($data)) {
            $cleaned = $this->cleanUtf8String($data);
            if (!$this->isValidUtf8($cleaned)) {
                Log::warning('Invalid UTF-8 string after cleaning', [
                    'original' => $data,
                    'cleaned' => $cleaned,
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                return '';
            }
            return $cleaned;
        }
        return $data;
    }

    private function validateFeedType(string $feedType): void
    {
        $cleanedFeedType = $this->cleanUtf8String($feedType);
        if (!in_array($cleanedFeedType, self::VALID_FEED_TYPES, true)) {
            Log::error('Invalid feed type', [
                'raw_feed_type' => $feedType,
                'cleaned_feed_type' => $cleanedFeedType,
                'valid_feed_types' => self::VALID_FEED_TYPES,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \InvalidArgumentException("Invalid feed type: {$cleanedFeedType}. Valid types: " . implode(', ', self::VALID_FEED_TYPES));
        }
    }


    /**
     * Generate the full SKU using the connection pair's prefix and the product's SKU
     */
    private function getPrefixedSku(Product $product): string
    {
        $prefix = $this->connectionPair->sku_prefix ?? '';
        $productSku = $product['sku'] ?? '';

        if (empty($prefix) || empty($productSku)) {
            Log::warning('Missing SKU prefix or product SKU', [
                'connection_pair_id' => $this->connectionPair->id,
                'prefix' => $prefix,
                'product_sku' => $productSku,
                'product_id' => $product['id']
            ]);
        }

        return trim($prefix . $productSku);
    }

   
    public function updateBulkListingPricesWithFeed(array $items): array
    {
        try {
            $validItems = [];

            foreach ($items as $item) {
                $prefix = $this->connectionPair->sku_prefix ?? '';
                $skuPrefix = $prefix . $item['sku'];
            
                $sku = $skuPrefix;
                if (empty($sku)) {
                    Log::warning('Skipping item with empty SKU', [
                        'item' => $item,
                        'connection_pair_id' => $this->connectionPair->id
                    ]);
                    continue;
                }

                $cleanedSku = $this->cleanUtf8String($sku);
                if (!$this->isValidUtf8($cleanedSku)) {
                    Log::warning('Skipping SKU with invalid UTF-8 encoding', [
                        'sku' => $sku,
                        'cleaned_sku' => $cleanedSku,
                        'connection_pair_id' => $this->connectionPair->id
                    ]);
                    continue;
                }


               

               // Add the item to validItems with the cleaned SKU
            $validItems[] = [
                'sku' => $cleanedSku,
                'sellerPrice' => $item['sellerPrice'] ?? 0,
                'quantity' => $item['quantity'] ?? 0
            ];
            }

            Log::info('Valid items for bulk update', [
                'valid_items' => $validItems,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            if (empty($validItems)) {
                throw new \Exception('No valid SKUs to process');
            }

            $feedType = 'POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA';
            $this->validateFeedType($feedType);

            $feedDocument = $this->createFeedDocument($feedType, true);
            $feedDocumentId = $feedDocument['feed_document_id'];
            $url = $feedDocument['url'];

            $tsvContent = "sku\tstandard_price\tquantity\n";
            foreach ($validItems as $item) {
                $sku = htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8');
                $price = number_format((float) $item['sellerPrice'], 2, '.', '');
                $quantity = (int) $item['quantity'];
                $tsvContent .= "{$sku}\t{$price}\t{$quantity}\n";
            }

            Log::debug('TSV feed content generated', [
                'feed_document_id' => $feedDocumentId,
                'feed_type' => $feedType,
                'content_preview' => substr($tsvContent, 0, 500),
                'connection_pair_id' => $this->connectionPair->id
            ]);

            $contentLength = $this->uploadFeedContent($url, $tsvContent, true);
            $feedId = $this->submitFeed($feedType, $feedDocumentId);

            Log::info('Feed submitted', [
                'feed_id' => $feedId,
                'feed_document_id' => $feedDocumentId,
                'feed_type' => $feedType,
                'content_length' => $contentLength,
                'connection_pair_id' => $this->connectionPair->id
            ]);

            return [
                'feed_id' => $feedId,
                'feed_document_id' => $feedDocumentId
            ];
        } catch (\Exception $e) {
            Log::error('Failed to submit feed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to submit feed: ' . $e->getMessage());
        }
    }

    protected function createFeedDocument(string $feedType, bool $gzip): array
    {
        $this->validateFeedType($feedType);
        $this->checkRateLimit('createFeedDocument');

        $path = "/feeds/" . self::FEEDS_API_VERSION . "/documents";
        $contentType = $feedType === 'JSON_LISTINGS_FEED' 
        ? 'application/json; charset=UTF-8' 
        : 'text/tab-separated-values; charset=UTF-8';
       // $contentType = 'text/tab-separated-values; charset=UTF-8';
        $effectiveContentType = $gzip ? $contentType . '; compressed=gzip' : $contentType;

        $payload = ['contentType' => $effectiveContentType];

        Log::info('Creating feed document', [
            'connection_pair_id' => $this->connectionPair->id,
            'feed_type' => $feedType,
            'content_type' => $effectiveContentType,
            'gzip' => $gzip
        ]);

        $signed = $this->signRequest('POST', $path, [], $payload);
        $response = Http::withHeaders($signed['headers'])->post($signed['url'], $payload);

        if ($response->failed()) {
            Log::error('Failed to create feed document', [
                'feed_type' => $feedType,
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id,
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to create feed document: ' . $response->body());
        }

        $data = $response->json();
        if (!isset($data['feedDocumentId']) || empty($data['feedDocumentId'])) {
            Log::error('Invalid or missing feedDocumentId in response', [
                'feed_type' => $feedType,
                'response' => $data,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Invalid or missing feedDocumentId in createFeedDocument response');
        }

        $rawFeedDocumentId = $data['feedDocumentId'];
        $data['feedDocumentId'] = $this->cleanUtf8String($rawFeedDocumentId);
        if (!$this->isValidUtf8($data['feedDocumentId'])) {
            Log::error('Invalid UTF-8 in feedDocumentId', [
                'raw_feed_document_id' => $rawFeedDocumentId,
                'cleaned_feed_document_id' => $data['feedDocumentId'],
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Feed document ID contains invalid UTF-8 characters');
        }

        // Validate feedDocumentId format
        if (!preg_match('/^amzn1\.tortuga\.[0-9]\.[a-z]{2}\.[a-f0-9-]+\.[A-Z0-9]+$/i', $data['feedDocumentId'])) {
            Log::error('Invalid feedDocumentId format', [
                'raw_feed_document_id' => $rawFeedDocumentId,
                'cleaned_feed_document_id' => $data['feedDocumentId'],
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Feed document ID has invalid format');
        }

        if (!isset($data['url']) || empty($data['url'])) {
            Log::error('Missing or empty URL in feed document response', [
                'feed_type' => $feedType,
                'response' => $data,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Feed document response missing URL');
        }

        // Avoid cleaning the URL to preserve query parameters
        Log::info('Feed document created', [
            'connection_pair_id' => $this->connectionPair->id,
            'raw_feed_document_id' => $rawFeedDocumentId,
            'cleaned_feed_document_id' => $data['feedDocumentId'],
            'url' => $data['url']
        ]);

        return [
            'feed_document_id' => $data['feedDocumentId'],
            'url' => $data['url']
        ];
    }

    protected function uploadFeedContent(string $url, string $content, bool $gzipEnabled): int
    {
        $headers = [
            'Content-Type' => 'text/tab-separated-values; charset=UTF-8'
        ];
        $body = $content;

        if ($gzipEnabled) {
            $headers['Content-Encoding'] = 'gzip';
            $body = gzencode($content, 9);
            if ($body === false) {
                Log::error('Failed to gzip feed content', [
                    'connection_pair_id' => $this->connectionPair->id,
                    'content_length' => strlen($content)
                ]);
                throw new \Exception('Failed to gzip feed content');
            }
            $headers['Content-Type'] = 'text/tab-separated-values; charset=UTF-8; compressed=gzip';
        }

        $contentLength = strlen($body);

        Log::info('Preparing to upload feed content', [
            'connection_pair_id' => $this->connectionPair->id,
            'url' => $url,
            'headers' => $headers,
            'content_length' => $contentLength,
            'gzip_enabled' => $gzipEnabled,
            'content_preview' => substr($content, 0, 500)
        ]);

        // Use raw Guzzle client for binary data
        $client = new Client();
        try {
            $response = $client->request('PUT', $url, [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() >= 400) {
                Log::error('Failed to upload feed content', [
                    'url' => $url,
                    'status' => $response->getStatusCode(),
                    'response' => (string) $response->getBody(),
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                throw new \Exception('Failed to upload feed content: ' . $response->getBody());
            }

            Log::info('Feed content uploaded successfully', [
                'connection_pair_id' => $this->connectionPair->id,
                'status' => $response->getStatusCode(),
                'response_headers' => $response->getHeaders()
            ]);

            return $contentLength;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Failed to upload feed content', [
                'url' => $url,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response',
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to upload feed content: ' . $e->getMessage());
        }
    }

    protected function submitFeed(string $feedType, string $feedDocumentId): string
    {
        $this->validateFeedType($feedType);
        $this->checkRateLimit('submitFeed');

        $path = "/feeds/" . self::FEEDS_API_VERSION . "/feeds";

        $cleanedFeedType = $this->cleanUtf8String($feedType);
        $cleanedFeedDocumentId = $this->cleanUtf8String($feedDocumentId);
        $cleanedMarketplaceId = $this->cleanUtf8String($this->getMarketplaceId());

        if (!$this->isValidUtf8($cleanedFeedType) || !$this->isValidUtf8($cleanedFeedDocumentId) || !$this->isValidUtf8($cleanedMarketplaceId)) {
            Log::error('Invalid UTF-8 in feed submission parameters', [
                'raw_feed_type' => $feedType,
                'cleaned_feed_type' => $cleanedFeedType,
                'raw_feed_document_id' => $feedDocumentId,
                'cleaned_feed_document_id' => $cleanedFeedDocumentId,
                'marketplace_id' => $this->getMarketplaceId(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Invalid UTF-8 characters in feed submission parameters');
        }

        if (empty($cleanedFeedDocumentId)) {
            Log::error('Empty feedDocumentId after cleaning', [
                'raw_feed_document_id' => $feedDocumentId,
                'cleaned_feed_document_id' => $cleanedFeedDocumentId,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Feed document ID is empty after cleaning');
        }

        $payload = [
            'feedType' => $cleanedFeedType,
            'marketplaceIds' => [$cleanedMarketplaceId],
            'inputFeedDocumentId' => $cleanedFeedDocumentId
        ];

        Log::info('Submitting feed', [
            'raw_feed_type' => $feedType,
            'cleaned_feed_type' => $cleanedFeedType,
            'raw_feed_document_id-semver' => $feedDocumentId,
            'cleaned_feed_document_id' => $cleanedFeedDocumentId,
            'marketplace_id' => $cleanedMarketplaceId,
            'payload' => $payload,
            'connection_pair_id' => $this->connectionPair->id
        ]);

        try {
            $signed = $this->signRequest('POST', $path, [], $payload);
        } catch (\JsonException $e) {
            Log::error('JSON encoding failed in signRequest', [
                'feed_type' => $cleanedFeedType,
                'raw_feed_document_id' => $feedDocumentId,
                'cleaned_feed_document_id' => $cleanedFeedDocumentId,
                'payload' => $payload,
                'error' => $e->getMessage(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('json_encode error: ' . $e->getMessage());
        }

        $response = Http::withHeaders($signed['headers'])->post($signed['url'], $payload);

        if ($response->failed()) {
            Log::error('Failed to submit feed', [
                'raw_feed_type' => $feedType,
                'cleaned_feed_type' => $cleanedFeedType,
                'raw_feed_document_id' => $feedDocumentId,
                'cleaned_feed_document_id' => $cleanedFeedDocumentId,
                'response' => $response->body(),
                'status' => $response->status(),
                'headers' => $response->headers(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to submit feed: ' . $response->body());
        }

        $data = $response->json();
        if (!isset($data['feedId']) || empty($data['feedId'])) {
            Log::error('Invalid or missing feedId in response', [
                'feed_type' => $cleanedFeedType,
                'raw_feed_document_id' => $feedDocumentId,
                'cleaned_feed_document_id' => $cleanedFeedDocumentId,
                'response' => $data,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Invalid or missing feedId in submitFeed response');
        }

        $data['feedId'] = $this->cleanUtf8String($data['feedId']);
        if (!$this->isValidUtf8($data['feedId'])) {
            Log::error('Invalid UTF-8 in feedId', [
                'feed_id' => $data['feedId'],
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Feed ID contains invalid UTF-8 characters');
        }

        Log::info('Feed submitted successfully', [
            'feed_id' => $data['feedId'],
            'connection_pair_id' => $this->connectionPair->id
        ]);

        return $data['feedId'];
    }

    public function getFeedStatus(string $feedId): array
    {
        $cleanedFeedId = $this->cleanUtf8String($feedId);
        if (!$this->isValidUtf8($cleanedFeedId)) {
            Log::error('Invalid UTF-8 in feedId for status check', [
                'feed_id' => $feedId,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Feed ID contains invalid UTF-8 characters');
        }

        $path = "/feeds/" . self::FEEDS_API_VERSION . "/feeds/{$cleanedFeedId}";

        Log::info('Checking feed status', [
            'feed_id' => $cleanedFeedId,
            'connection_pair_id' => $this->connectionPair->id
        ]);

        $signed = $this->signRequest('GET', $path);
        $response = Http::withHeaders($signed['headers'])->get($signed['url']);

        if ($response->failed()) {
            Log::error('Failed to get feed status', [
                'feed_id' => $cleanedFeedId,
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id,
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to get feed status: ' . $response->body());
        }

        $data = $response->json();
        Log::info('Feed status', [
            'feed_id' => $cleanedFeedId,
            'status' => $data['processingStatus'],
            'connection_pair_id' => $this->connectionPair->id
        ]);

        return $data;
    }


    public function getFeedResult(string $resultDocumentId): array
{
    Log::info('Retrieving feed result', [
        'result_document_id' => $resultDocumentId,
        'connection_pair_id' => $this->connectionPair->id
    ]);
    
    try {
        // First get the feed result document URL from Amazon
        $path = "/feeds/" . self::FEEDS_API_VERSION . "/documents/" . $resultDocumentId;
        $signed = $this->signRequest('GET', $path);
        $response = Http::withHeaders($signed['headers'])->get($signed['url']);

        if ($response->failed()) {
            Log::error('Failed to get feed result document URL', [
                'result_document_id' => $resultDocumentId,
                'status' => $response->status(),
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to get feed result document URL: ' . $response->body());
        }

        $documentData = $response->json();
        if (!isset($documentData['url'])) {
            throw new \Exception('Feed result document URL not found in response');
        }

        // Now download the actual document from the pre-signed URL
        $contentResponse = Http::get($documentData['url']);
        
        if ($contentResponse->failed()) {
            Log::error('Failed to download feed result content', [
                'result_document_id' => $resultDocumentId,
                'status' => $contentResponse->status(),
                'response' => $contentResponse->body(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to download feed result content: ' . $contentResponse->body());
        }

        $content = $contentResponse->body();
        
        // Check if we got XML error response
        if (stripos($content, '<?xml') === 0 && stripos($content, '<Error>') !== false) {
            $xml = simplexml_load_string($content);
            Log::error('Amazon S3 error response', [
                'result_document_id' => $resultDocumentId,
                'error_code' => (string)$xml->Code,
                'error_message' => (string)$xml->Message,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Amazon S3 error: ' . (string)$xml->Message);
        }

        // Process the content based on the response content type
        $contentType = $contentResponse->header('Content-Type');
        if (strpos($contentType, 'application/xml') !== false || strpos($contentType, 'text/xml') !== false) {
            libxml_use_internal_errors(true);
            $xml = new \SimpleXMLElement($content);
            libxml_use_internal_errors(false);
            $result = $this->xmlToArray($xml);
        } else {
            // Handle other content types (e.g., JSON) if needed
            $result = ['raw_content' => $content];
        }

        Log::info('Successfully parsed feed result', [
            'result_document_id' => $resultDocumentId,
            'content_type' => $contentType,
            'connection_pair_id' => $this->connectionPair->id
        ]);

        return $result;

    } catch (\Exception $e) {
        Log::error('Failed to process feed result', [
            'result_document_id' => $resultDocumentId,
            'error' => $e->getMessage(),
            'connection_pair_id' => $this->connectionPair->id
        ]);
        throw new \Exception('Failed to process feed result: ' . $e->getMessage());
    }
}

    // public function getFeedResult(string $resultDocumentId): array
    // {
    //     Log::info('Retrieving feed result', [
    //         'result_document_id' => $resultDocumentId,
    //         'connection_pair_id' => $this->connectionPair->id
    //     ]);
    //     try {
    //         $response = Http::get($this->getFeedResultUrl($resultDocumentId));

    //         if ($response->failed()) {
    //             Log::error('Failed to retrieve feed result document', [
    //                 'result_document_id' => $resultDocumentId,
    //                 'status' => $response->status(),
    //                 'response' => $response->body(),
    //                 'connection_pair_id' => $this->connectionPair->id
    //             ]);
    //             throw new \Exception('Failed to retrieve feed result document: ' . $response->body());
    //         }

    //         $content = $response->body();
    //         $headers = $response->headers();
    //         $contentLength = strlen($content);

    //         Log::debug('Raw feed result content', [
    //             'result_document_id' => $resultDocumentId,
    //             'content_length' => $contentLength,
    //             'content_preview' => substr($content, 0, 500),
    //             'headers' => $headers,
    //             'connection_pair_id' => $this->connectionPair->id
    //         ]);

    //         libxml_use_internal_errors(true);
    //         $dom = new \DOMDocument();
    //         if (!$dom->loadXML($content)) {
    //             $errors = libxml_get_errors();
    //             Log::error('Invalid feed result XML', [
    //                 'result_document_id' => $resultDocumentId,
    //                 'errors' => $errors,
    //                 'content_preview' => substr($content, 0, 500),
    //                 'connection_pair_id' => $this->connectionPair->id
    //             ]);
    //             throw new \Exception('Invalid feed result XML: ' . json_encode($errors));
    //         }

    //         $xml = new \SimpleXMLElement($content);
    //         libxml_use_internal_errors(false);

    //         $result = $this->xmlToArray($xml);

    //         Log::info('Parsed feed result', [
    //             'result_document_id' => $resultDocumentId,
    //             'result' => $result,
    //             'connection_pair_id' => $this->connectionPair->id
    //         ]);

    //         return $result;
    //     } catch (\Exception $e) {
    //         Log::error('Failed to parse feed result XML', [
    //             'result_document_id' => $resultDocumentId,
    //             'error' => $e->getMessage(),
    //             'content_preview' => substr($content ?? '', 0, 500),
    //             'connection_pair_id' => $this->connectionPair->id
    //         ]);
    //         throw new \Exception('Failed to parse feed result XML: ' . $e->getMessage());
    //     }
    // }

    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $array = json_decode(json_encode($xml), true);
        return $this->normalizeXmlArray($array);
    }

    private function normalizeXmlArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->normalizeXmlArray($value);
                if (isset($value[0]) && count($value) === 1) {
                    $array[$key] = $value[0];
                }
            }
        }
        return $array;
    }

    protected function getFeedResultUrl(string $resultDocumentId): string
    {
        return "https://tortuga-prod-na.s3-external-1.amazonaws.com/{$resultDocumentId}";
    }

    private function validateSku(string $sku): bool
    {
        $cleanedSku = $this->cleanUtf8String($sku);
        if (!$this->isValidUtf8($cleanedSku)) {
            Log::warning('Invalid UTF-8 in SKU during validation', [
                'sku' => $sku,
                'cleaned_sku' => $cleanedSku,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            return false;
        }

        $path = sprintf("/listings/%s/items/%s/%s", self::LISTINGS_API_VERSION, self::SELLER_ID, $cleanedSku);
        $query = ['marketplaceIds' => self::MARKETPLACE_ID];

        Log::info('Validating SKU', [
            'sku' => $cleanedSku,
            'connection_pair_id' => $this->connectionPair->id,
            'path' => $path,
        ]);

        $signed = $this->signRequest('GET', $path, $query);
        $response = Http::withHeaders($signed['headers'])->get($signed['url']);

        if ($response->successful()) {
            Log::info('SKU validated successfully', [
                'sku' => $cleanedSku,
                'connection_pair_id' => $this->connectionPair->id,
            ]);
            return true;
        }

        Log::warning('SKU validation failed', [
            'sku' => $cleanedSku,
            'connection_pair_id' => $this->connectionPair->id,
            'response' => $response->json(),
            'status' => $response->status(),
        ]);
        return false;
    }



       /**
     * Add an existing catalog item to seller catalog
     */
    public function addToSellerCatalog(Product $product, $asin, float $sellerPrice, int $quantity, string $product_category): bool
    {
        // Step 1: Get the ASIN from catalog data
        // $catalogData = $product->catalog_data ?? [];
        $catalogItem = $product->catalog_data['items'][0] ?? null;

        if (empty($asin)) {
            Log::error('ASIN is empty', [
                'product_id' => $product->id,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception("ASIN is empty");
        }

        Log::info('Catalog data', [
            'catalog_data' => $catalogItem,
            'product' => $product,
            'connection_pair_id' => $this->connectionPair->id
        ]);


        // Step 2: Get product type using ASIN
        $keyword = $this->getProductTypeByAsin($asin);
        $productType = $this->getAllProductTypes($keyword);
        $productType = $productType[0] ?? null;

        Log::info('Product type determined', [
            'asin' => $asin,
            'product_type' => $productType
        ]);

        if (!$productType) {
            throw new \Exception("Could not determine product type for ASIN: {$asin}");
        }

        // Step 3: Create the listing using Listings Items API
        $path = sprintf(
            "/listings/2021-08-01/items/%s/%s",
            self::SELLER_ID,
            $this->getPrefixedSku($product)
        );

        // Prepare the payload for the request
        $payload = [
            'productType' => $productType,
            'requirements' => 'LISTING_OFFER_ONLY',
            'attributes' => [
                'merchant_suggested_asin' => [
                    [
                        'value' => $asin,
                        'marketplace_id' => self::MARKETPLACE_ID
                    ]
                ],
                'condition_type' => [
                    [
                        'value' => 'new_new',
                        'marketplace_id' => self::MARKETPLACE_ID
                    ]
                ],
                'purchasable_offer' => [
                    [
                        'marketplace_id' => self::MARKETPLACE_ID,
                        'currency' => 'CAD',
                        'our_price' => [
                            [
                                'schedule' => [
                                    [
                                        'value_with_tax' => $sellerPrice,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'fulfillment_availability' => [
                    [
                        'fulfillment_channel_code' => 'DEFAULT',
                        'quantity' => $quantity,
                        'marketplace_id' => self::MARKETPLACE_ID,
                    ]
                ]
            ]
        ];

        // Log the payload for debugging
        Log::info('Payload for Seller Catalog Addition', [
            'payload' => $payload
        ]);

        // First, validate the listing
        $query = [
            'marketplaceIds' => self::MARKETPLACE_ID,
            'requirements' => 'LISTING_OFFER_ONLY'
        ];

        $signed = $this->signRequest('PUT', $path, $query, $payload);
        $response = Http::withHeaders($signed['headers'])->put($signed['url'], $payload);

        Log::info('Response from Amazon', [
            'status' => $response->status(),
            'response' => $response->json()
        ]);

        if ($response->failed()) {
            Log::error('Failed to add product to seller catalog', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception("Failed to add product to seller catalog: " . json_encode($response->json()));
        }

        return in_array($response->status(), [200, 202], true);
    }

      /**
     * Get all available Amazon product types
     */
    public function getAllProductTypes(string $product_category): array
    {
        $path = "/definitions/2020-09-01/productTypes";

        // Clean and normalize the product category for better API compatibility
        $keywords = $this->normalizeProductCategory($product_category);

        $query = [
            'marketplaceIds' => self::MARKETPLACE_ID,
            'keywords' => $keywords,
        ];

        Log::info('Getting all product types', [
            'path' => $path,
            'query' => $query,
            'marketplace_id' => self::MARKETPLACE_ID
        ]);

        $signed = $this->signRequest('GET', $path, $query);
        $response = Http::withHeaders($signed['headers'])->get($signed['url']);

        Log::info('Response from Amazon', [
            'status' => $response->status(),
            'response' => $response->json()
        ]);
        if ($response->failed()) {
            Log::error('Failed to get product types', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to get Amazon product types: ' . $response->body());
        }

        $productTypes = $response->json('productTypes', []);

        Log::info('Product types response', [
            'product_types' => $productTypes
        ]);

        // Extract only the 'name' attributes
        $productTypeNames = array_map(fn($type) => $type['name'], $productTypes);

        return $productTypeNames;
    }

    /**
     * Normalize product category for Amazon API compatibility
     * Returns comma-delimited keywords for better search results
     * 
     * @param string $product_category
     * @return string
     */
    private function normalizeProductCategory(string $product_category): string
    {
        // Handle specific category mappings for better Amazon API compatibility
        $categoryMappings = [
            'Displays' => 'monitors',
            'Accessories & Supplies' => 'computer,accessories',
            'Computer Accessories & Peripherals' => 'computer,accessories',
            'Office & School Supplies' => 'office,supplies',
            'Electronics & Accessories' => 'electronics',
            'Audio & Video Accessories' => 'audio,video',
            'Camera & Photo Accessories' => 'camera,photo',
            'Cell Phone Accessories & Parts' => 'cell,phone,accessories',
            'Computer Components & Parts' => 'computer,components',
            'Video Games & Accessories' => 'video,games',
        ];

        // Check if we have a specific mapping
        if (isset($categoryMappings[$product_category])) {
            return $categoryMappings[$product_category];
        }

        // Clean the category name by removing special characters and normalizing
        $normalized = $product_category;
        
        // Replace common problematic characters
        $normalized = str_replace(['&', ' & '], [' and ', ' and '], $normalized);
        
        // Remove extra spaces and trim
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        
        // Extract meaningful words and convert to comma-delimited keywords
        $words = explode(' ', $normalized);
        $keywords = array_filter($words, function($word) {
            $word = strtolower(trim($word));
            // Filter out common stop words and short words
            return strlen($word) > 2 && !in_array($word, ['and', 'or', 'with', 'for', 'the', 'of', 'in', 'on', 'at', 'by', 'from']);
        });
        
        // Convert to lowercase and join with commas
        $keywordString = implode(',', array_map('strtolower', array_slice($keywords, 0, 4)));
        
        // If no meaningful keywords found, use the original category as a single keyword
        if (empty($keywordString)) {
            $keywordString = strtolower(str_replace([' ', '&'], ['', ''], $product_category));
        }
        
        Log::info('Normalized product category to keywords', [
            'original' => $product_category,
            'keywords' => $keywordString
        ]);
        
        return $keywordString;
    }

    /**
     * Get product type by ASIN from Amazon Catalog API
     *
     * @param string $asin
     * @return string|null
     * @throws \Exception
     */
    public function getProductTypeByAsin(string $asin): ?string
    {
        $path = "/catalog/2022-04-01/items/{$asin}";
        $query = [
            'marketplaceIds' => self::MARKETPLACE_ID,
            'includedData' => ['attributes', 'dimensions', 'identifiers', 'summaries']
        ];

        Log::info('Getting product type by ASIN', [
            'asin' => $asin,
            'path' => $path,
            'query' => $query
        ]);

        try {
            $signed = $this->signRequest('GET', $path, $query);
            $response = Http::withHeaders($signed['headers'])->get($signed['url']);

            if ($response->failed()) {
                Log::error('Failed to get product type by ASIN', [
                    'asin' => $asin,
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to get product type: ' . ($response['errors'][0]['message'] ?? 'Unknown error'));
            }

            $data = $response->json();
            
            // Try to get the product type from browseClassification
            if (isset($data['summaries'][0]['browseClassification']['displayName'])) {
                return $data['summaries'][0]['browseClassification']['displayName'];
            }

            Log::warning('No browse classification found for ASIN', [
                'asin' => $asin,
                'response' => $data
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error getting product type by ASIN', [
                'asin' => $asin,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

      /**
     * Search for a product in the Amazon catalog by UPC
     */
    public function searchCatalogItemByUpc(string $upc): ?array
    {
        $path = "/catalog/" . self::CATALOG_API_VERSION . "/items";
        $query = [
            'identifiers' => $upc,
            'identifiersType' => 'UPC',
            'marketplaceIds' => self::MARKETPLACE_ID,
        ];

        Log::info('Searching Amazon catalog by UPC', [
            'upc' => $upc,
            'path' => $path,
            'query' => $query,
            'region' => $this->region,
            'marketplace_id' => self::MARKETPLACE_ID
        ]);

        $signed = $this->signRequest('GET', $path, $query);
        $response = Http::withHeaders($signed['headers'])->get($signed['url']);

        $responseData = $response->json();

        Log::info('Amazon catalog search response', [
            'status' => $response->status(),
            'response_data' => $responseData,
            'headers' => $response->headers()
        ]);

        if ($response->failed()) {
            Log::error('Catalog search failed', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'upc' => $upc,
                'response' => $responseData,
                'status' => $response->status(),
                'error_details' => $response->body()
            ]);
            return null;
        }

        return $responseData;
    }



    /**
 * Search for a single product in the Amazon catalog by UPC or EAN
 *
 * @param string $upc UPC or EAN to search
 * @return array|null Catalog data (asin, catalog_item) if found, null if not found
 * @throws \Exception
 */

    /**
 * Add multiple products to the seller catalog using JSON_LISTINGS_FEED
 *
 * @param array $items Array of items, each containing Product, sellerPrice, quantity, and product_category
 * @return array Containing feed_id and feed_document_id
 * @throws \Exception
 */
public function addBulkToSellerCatalogWithJsonFeed(array $items): array
{
    try {
        $validItems = [];

        foreach ($items as $item) {
            if (!isset($item['product']) || !($item['product'] instanceof Product) ||
                !isset($item['sellerPrice']) || !is_numeric($item['sellerPrice']) ||
                !isset($item['quantity']) || !is_numeric($item['quantity']) ||
                !isset($item['product_category'])) {
                Log::warning('Skipping invalid item in bulk catalog add', [
                    'item' => $item,
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                continue;
            }

            $product = $item['product'];
            $catalogData = $product->catalog_data ?? [];
            $catalogItem = $catalogData['items'][0] ?? null;

            if (!$catalogItem || empty($catalogItem['asin'])) {
                Log::warning('Skipping product with no ASIN', [
                    'product_id' => $product->id,
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                continue;
            }

            $asin = $catalogItem['asin'];
            $sku = $this->getPrefixedSku($product);
            $cleanedSku = $this->cleanUtf8String($sku);
            if (!$this->isValidUtf8($cleanedSku) || empty($cleanedSku)) {
                Log::error('Invalid or empty SKU', [
                    'sku' => $sku,
                    'cleaned_sku' => $cleanedSku,
                    'product_id' => $product->id,
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                continue;
            }

            $keyword = $this->getProductTypeByAsin($asin);
            $productTypes = $this->getAllProductTypes($keyword);
            $productType = $productTypes[0] ?? null;

            if (!$productType) {
                Log::warning('Could not determine product type for ASIN', [
                    'asin' => $asin,
                    'product_id' => $product->id,
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                continue;
            }

            $validItems[] = [
                'sku' => $cleanedSku,
                'asin' => $asin,
                'product_type' => $productType,
                'seller_price' => (float) $item['sellerPrice'],
                'quantity' => (int) $item['quantity'],
                'condition' => 'new_new',
            ];
        }

        if (empty($validItems)) {
            throw new \Exception('No valid items to process for bulk catalog add');
        }

        $feedType = 'JSON_LISTINGS_FEED';
        $this->validateFeedType($feedType);

        $feedDocument = $this->createFeedDocument($feedType, false);
        $feedDocumentId = $feedDocument['feed_document_id'];
        $url = $feedDocument['url'];

        $jsonContent = [
            'header' => [
                'sellerId' => self::SELLER_ID,
                'version' => '1.02',
                'issueLocale' => 'en_US',
            ],
            'messages' => []
        ];

        foreach ($validItems as $index => $item) {
            $jsonContent['messages'][] = [
                'messageID' => (string) ($index + 1),
                'operationType' => 'UPDATE',
                'sku' => $item['sku'],
                'productType' => $item['product_type'],
                'marketplaceIds' => [self::MARKETPLACE_ID],
                'attributes' => [
                    'merchant_suggested_asin' => [
                        [
                            'value' => $item['asin'],
                            'marketplaceId' => self::MARKETPLACE_ID
                        ]
                    ],
                    'condition_type' => [
                        [
                            'value' => $item['condition'],
                            'marketplaceId' => self::MARKETPLACE_ID
                        ]
                    ],
                    'purchasable_offer' => [
                        [
                            'marketplaceId' => self::MARKETPLACE_ID,
                            'currency' => 'CAD',
                            'ourPrice' => [
                                [
                                    'schedule' => [
                                        [
                                            'valueWithTax' => $item['seller_price'],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'fulfillment_availability' => [
                        [
                            'fulfillmentChannelCode' => 'DEFAULT',
                            'quantity' => $item['quantity'],
                            'marketplaceId' => self::MARKETPLACE_ID,
                        ]
                    ]
                ]
            ];
        }

        $jsonString = json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonString === false) {
            throw new \Exception('Failed to encode JSON feed content');
        }

        // Log JSON content for debugging
        $logPath = storage_path('logs/json_feed_' . $feedDocumentId . '.json');
        file_put_contents($logPath, $jsonString);
        Log::debug('JSON feed content generated', [
            'feed_document_id' => $feedDocumentId,
            'feed_type' => $feedType,
            'log_path' => $logPath,
            'content_preview' => substr($jsonString, 0, 500),
            'connection_pair_id' => $this->connectionPair->id
        ]);

        $contentLength = $this->uploadFeedContent($url, $jsonString, false);
        $feedId = $this->submitFeed($feedType, $feedDocumentId);

        Log::info('Bulk catalog add feed submitted', [
            'feed_id' => $feedId,
            'feed_document_id' => $feedDocumentId,
            'feed_type' => $feedType,
            'content_length' => $contentLength,
            'connection_pair_id' => $this->connectionPair->id
        ]);

        return [
            'feed_id' => $feedId,
            'feed_document_id' => $feedDocumentId
        ];
    } catch (\Exception $e) {
        Log::error('Failed to submit bulk catalog add JSON feed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'connection_pair_id' => $this->connectionPair->id
        ]);
        throw new \Exception('Failed to submit bulk catalog add JSON feed: ' . $e->getMessage());
    }
}

/**
 * Search for a single product in the Amazon catalog by UPC or EAN
 *
 * @param string $upc UPC or EAN to search
 * @return array|null Catalog data for the matching item, or null if not found
 * @throws \Exception
 */
// public function searchCatalogItemByUpc(string $upc): ?array
// {
//     try {
//         // Validate UPC/EAN
//         if (!preg_match('/^\d{12,13}$/', $upc)) {
//             Log::warning('Invalid UPC/EAN format', [
//                 'upc' => $upc,
//                 'connection_pair_id' => $this->connectionPair->id,
//             ]);
//             return null;
//         }

//         // Normalize: Remove leading zeros
//         $normalizedUpc = ltrim($upc, '0');

//         $this->checkRateLimit('searchCatalogItems');

//         $path = "/catalog/" . self::CATALOG_API_VERSION . "/items";
//         $query = [
//             'identifiers' => $upc,
//             'identifiersType' => 'UPC',
//             'marketplaceIds' => self::MARKETPLACE_ID,
//             'includedData' => 'attributes,dimensions,identifiers,summaries',
//         ];

//         Log::info('Sending catalog search request for single UPC', [
//             'upc' => $upc,
//             'marketplace_id' => self::MARKETPLACE_ID,
//             'connection_pair_id' => $this->connectionPair->id,
//         ]);

//         $signed = $this->signRequest('GET', $path, $query);
//         $client = new \GuzzleHttp\Client();
//         $response = $client->request('GET', $signed['url'], [
//             'headers' => $signed['headers'],
//             'timeout' => 30,
//         ]);

//         $responseData = json_decode($response->getBody(), true);

//         // Log full response
//         $logPath = storage_path('logs/catalog_response_' . time() . '_upc_' . $upc . '.json');
//         file_put_contents($logPath, json_encode($responseData, JSON_PRETTY_PRINT));
//         Log::debug('Catalog API response saved', [
//             'upc' => $upc,
//             'log_path' => $logPath,
//             'connection_pair_id' => $this->connectionPair->id,
//         ]);

//         $items = $responseData['items'] ?? [];
//         if (empty($items)) {
//             Log::warning('No items found for UPC', [
//                 'upc' => $upc,
//                 'connection_pair_id' => $this->connectionPair->id,
//             ]);
//             return null;
//         }

//         foreach ($items as $item) {
//             $asin = $item['asin'] ?? null;
//             if (!$asin) {
//                 Log::warning('Item missing ASIN', [
//                     'upc' => $upc,
//                     'item' => $item,
//                     'connection_pair_id' => $this->connectionPair->id,
//                 ]);
//                 continue;
//             }

//             $identifiers = $item['identifiers'] ?? [];
//             foreach ($identifiers as $identifierSet) {
//                 if (!isset($identifierSet['identifiers'])) {
//                     continue;
//                 }
//                 foreach ($identifierSet['identifiers'] as $identifier) {
//                     if (in_array($identifier['identifierType'], ['UPC', 'EAN'])) {
//                         $responseIdentifier = ltrim($identifier['identifier'], '0');
//                         if ($responseIdentifier === $normalizedUpc) {
//                             Log::info('Matched UPC/EAN to ASIN', [
//                                 'input_upc' => $upc,
//                                 'response_identifier' => $identifier['identifier'],
//                                 'normalized_upc' => $normalizedUpc,
//                                 'asin' => $asin,
//                                 'connection_pair_id' => $this->connectionPair->id,
//                             ]);
//                             return [
//                                 'asin' => $asin,
//                                 'catalog_item' => $item,
//                             ];
//                         }
//                     }
//                 }
//             }
//         }

//         Log::warning('No matching UPC/EAN found in response', [
//             'upc' => $upc,
//             'connection_pair_id' => $this->connectionPair->id,
//         ]);
//         return null;
//     } catch (\Exception $e) {
//         Log::error('Failed to search catalog for UPC', [
//             'upc' => $upc,
//             'error' => $e->getMessage(),
//             'connection_pair_id' => $this->connectionPair->id,
//         ]);
//         return null;
//     }
// }

/**
     * Delete an item from the seller catalog
     *
     * @param Product $product The product to delete
     * @return bool True if deletion was successful
     * @throws \Exception
     */
    public function deleteFromSellerCatalog(Product $product): bool
    {
        $sku = $this->getPrefixedSku($product);
        $cleanedSku = $this->cleanUtf8String($sku);

        if (!$this->isValidUtf8($cleanedSku) || empty($cleanedSku)) {
            Log::error('Invalid or empty SKU for deletion', [
                'sku' => $sku,
                'cleaned_sku' => $cleanedSku,
                'product_id' => $product->id,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Invalid or empty SKU');
        }

        $path = sprintf(
            "/listings/2021-08-01/items/%s/%s",
            self::SELLER_ID,
            $cleanedSku
        );

        $query = [
            'marketplaceIds' => [self::MARKETPLACE_ID],
            'issueLocale' => 'en_US'
        ];

        Log::info('Deleting product from seller catalog', [
            'sku' => $cleanedSku,
            'product_id' => $product->id,
            'connection_pair_id' => $this->connectionPair->id
        ]);

        try {
            $signed = $this->signRequest('DELETE', $path, $query);
            $response = Http::withHeaders($signed['headers'])->delete($signed['url']);

            if ($response->failed()) {
                // Check if it's a 404 (item not found) - we'll consider this a "successful" deletion
                if ($response->status() === 404) {
                    Log::info('Product was already deleted or does not exist in seller catalog', [
                        'sku' => $cleanedSku,
                        'product_id' => $product->id,
                        'connection_pair_id' => $this->connectionPair->id
                    ]);
                    return true;
                }

                Log::error('Failed to delete product from seller catalog', [
                    'sku' => $cleanedSku,
                    'product_id' => $product->id,
                    'response' => $response->json(),
                    'status' => $response->status(),
                    'connection_pair_id' => $this->connectionPair->id
                ]);
                throw new \Exception('Failed to delete product: ' . ($response->json()['errors'][0]['message'] ?? 'Unknown error'));
            }

            Log::info('Successfully deleted product from seller catalog', [
                'sku' => $cleanedSku,
                'product_id' => $product->id,
                'connection_pair_id' => $this->connectionPair->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception while deleting product from seller catalog', [
                'sku' => $cleanedSku,
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw $e;
        }
    }

    /**
     * Delete multiple items from the seller catalog using a feed
     *
     * @param Product[] $products Array of products to delete
     * @return array Containing feed_id and feed_document_id
     * @throws \Exception
     */
    public function deleteBulkFromSellerCatalog(array $products): array
    {
        try {
            $validSkus = [];

            foreach ($products as $product) {
                if (!($product instanceof Product)) {
                    Log::warning('Invalid product object in bulk delete', [
                        'product' => $product,
                        'connection_pair_id' => $this->connectionPair->id
                    ]);
                    continue;
                }

                $sku = $this->getPrefixedSku($product);
                $cleanedSku = $this->cleanUtf8String($sku);

                if (!$this->isValidUtf8($cleanedSku) || empty($cleanedSku)) {
                    Log::warning('Invalid or empty SKU in bulk delete', [
                        'sku' => $sku,
                        'cleaned_sku' => $cleanedSku,
                        'product_id' => $product->id,
                        'connection_pair_id' => $this->connectionPair->id
                    ]);
                    continue;
                }

                $validSkus[] = $cleanedSku;
            }

            if (empty($validSkus)) {
                throw new \Exception('No valid SKUs to process for bulk deletion');
            }

            $feedType = 'JSON_LISTINGS_FEED';
            $this->validateFeedType($feedType);

            $feedDocument = $this->createFeedDocument($feedType, false);
            $feedDocumentId = $feedDocument['feed_document_id'];
            $url = $feedDocument['url'];

            $jsonContent = [
                'header' => [
                    'sellerId' => self::SELLER_ID,
                    'version' => '1.02',
                    'issueLocale' => 'en_US',
                ],
                'messages' => []
            ];

            foreach ($validSkus as $index => $sku) {
                $jsonContent['messages'][] = [
                    'messageId' => (string) ($index + 1),
                    'operationType' => 'DELETE',
                    'sku' => $sku,
                    'marketplaceIds' => [self::MARKETPLACE_ID]
                ];
            }

            $jsonString = json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($jsonString === false) {
                throw new \Exception('Failed to encode JSON feed content for bulk deletion');
            }

            // Log JSON content for debugging
            $logPath = storage_path('logs/delete_feed_' . $feedDocumentId . '.json');
            file_put_contents($logPath, $jsonString);
            Log::debug('JSON delete feed content generated', [
                'feed_document_id' => $feedDocumentId,
                'feed_type' => $feedType,
                'log_path' => $logPath,
                'content_preview' => substr($jsonString, 0, 500),
                'connection_pair_id' => $this->connectionPair->id
            ]);

            $contentLength = $this->uploadFeedContent($url, $jsonString, false);
            $feedId = $this->submitFeed($feedType, $feedDocumentId);

            Log::info('Bulk delete feed submitted', [
                'feed_id' => $feedId,
                'feed_document_id' => $feedDocumentId,
                'feed_type' => $feedType,
                'content_length' => $contentLength,
                'connection_pair_id' => $this->connectionPair->id,
                'sku_count' => count($validSkus)
            ]);

            return [
                'feed_id' => $feedId,
                'feed_document_id' => $feedDocumentId
            ];
        } catch (\Exception $e) {
            Log::error('Failed to submit bulk delete feed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to submit bulk delete feed: ' . $e->getMessage());
        }
    }
}