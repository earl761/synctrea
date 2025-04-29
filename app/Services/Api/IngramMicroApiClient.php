<?php

namespace App\Services\Api;

use App\Models\Supplier;
use App\Services\Api\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IngramMicroApiClient extends AbstractApiClient
{
    protected Supplier $supplier;
    protected string $apiKey;
    protected string $apiSecret;
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // milliseconds
    protected int $requestsPerMinute = 60;
    protected array $requestTimestamps = [];

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
        $this->baseUrl = $supplier->api_endpoint;
        $this->apiKey = $supplier->api_key;
        $this->apiSecret = $supplier->api_secret;
    }

    protected function shouldRateLimit(): bool
    {
        // Clean up old timestamps
        $this->requestTimestamps = array_filter($this->requestTimestamps, function ($timestamp) {
            return $timestamp > time() - 60;
        });

        return count($this->requestTimestamps) >= $this->requestsPerMinute;
    }

    protected function trackRequest(): void
    {
        $this->requestTimestamps[] = time();
    }

    protected function waitForRateLimit(): void
    {
        if ($this->shouldRateLimit()) {
            $oldestTimestamp = min($this->requestTimestamps);
            $waitTime = 60 - (time() - $oldestTimestamp);
            if ($waitTime > 0) {
                sleep($waitTime);
            }
        }
    }

    protected function executeWithRetry(callable $operation)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $this->waitForRateLimit();
                $this->trackRequest();
                return $operation();
            } catch (ApiException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $this->maxRetries && $this->shouldRetry($e)) {
                    usleep($this->retryDelay * 1000 * pow(2, $attempts - 1));
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException;
    }

    protected function shouldRetry(ApiException $e): bool
    {
        $retryableStatusCodes = [408, 429, 500, 502, 503, 504];
        return in_array($e->getCode(), $retryableStatusCodes);
    }

    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        // Set up authentication headers
        $this->setHeaders([
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $this->isInitialized = true;
    }

    public function getCatalog(array $params = []): array
    {
        $defaultParams = [
            'pageNumber' => 1,
            'pageSize' => 25,
            'type' => 'IM::physical'
        ];

        $queryParams = array_merge($defaultParams, array_filter($params));
        
        // Validate and adjust parameters
        $queryParams['pageSize'] = min($queryParams['pageSize'], 100);
        
        if (isset($queryParams['vendor']) && !is_array($queryParams['vendor'])) {
            $queryParams['vendor'] = [$queryParams['vendor']];
        }
        
        if (isset($queryParams['vendorPartNumber']) && !is_array($queryParams['vendorPartNumber'])) {
            $queryParams['vendorPartNumber'] = [$queryParams['vendorPartNumber']];
        }
        
        if (isset($queryParams['keyword']) && !is_array($queryParams['keyword'])) {
            $queryParams['keyword'] = [$queryParams['keyword']];
        }

        return $this->executeWithRetry(function () use ($queryParams) {
            $this->setHeaders([
                'IM-CustomerNumber' => "70-509102", //$this->supplier->customer_number,
               // 'IM-SenderID' => $this->supplier->sender_id,
                'IM-CorrelationID' => bin2hex(random_bytes(16)), // Generate 32 character random string
                'IM-CountryCode' => $this->supplier->country_code ?? 'US',
                'Accept-Language' => app()->getLocale() ?? 'en'
            ]);

            $response = $this->request('GET', 'sandbox/resellers/v6/catalog', $queryParams);

            Log::info('Ingram Micro API response:', $response->json());
           
            return $this->handleResponse($response);
        });
    }

    public function getProduct(string $sku): array
    {
        return $this->executeWithRetry(function () use ($sku) {
            $response = $this->request('GET', "products/{$sku}");
            return $this->handleResponse($response);
        });
    }

    public function getProducts(array $skus = []): array
    {
        return $this->executeWithRetry(function () use ($skus) {
            $params = [];
            if (!empty($skus)) {
                $params['skus'] = implode(',', $skus);
            }
            $response = $this->request('GET', 'products', $params);
            return $this->handleResponse($response);
        });
    }

    public function updateProduct(array $productData): array
    {
        return $this->executeWithRetry(function () use ($productData) {
            $response = $this->request('PUT', "products/{$productData['sku']}", $productData);
            return $this->handleResponse($response);
        });
    }

    public function updateInventory(string $sku, int $quantity): array
    {
        return $this->executeWithRetry(function () use ($sku, $quantity) {
            $response = $this->request('PUT', "products/{$sku}/inventory", [
                'quantity' => $quantity
            ]);
            return $this->handleResponse($response);
        });
    }

    public function updatePrice(string $sku, float $price): array
    {
        return $this->executeWithRetry(function () use ($sku, $price) {
            $response = $this->request('PUT', "products/{$sku}/price", [
                'price' => $price
            ]);
            return $this->handleResponse($response);
        });
    }

    public function getPriceAndAvailability(array $params = []): array
    {
        // Validate and prepare products array
        if (!isset($params['products']) || !is_array($params['products'])) {
            throw new ApiException('Products array is required', 400);
        }

        // Ensure each product has exactly one identifier type
        foreach ($params['products'] as $product) {
            $identifiers = array_filter([
                isset($product['ingramPartNumber']),
                isset($product['vendorPartNumber']),
                isset($product['upc'])
            ]);

            if (count($identifiers) !== 1) {
                throw new ApiException('Each product must have exactly one identifier type (ingramPartNumber, vendorPartNumber, or upc)', 400);
            }
        }

        return $this->executeWithRetry(function () use ($params) {
            // Generate a unique 32-character correlation ID
            $correlationId = bin2hex(random_bytes(16));

            // Set required headers for the API request
            $this->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'IM-CustomerNumber' => $this->supplier->customer_number ?? '70-509102',
                'IM-CountryCode' => $this->supplier->country_code ?? 'US',
                'IM-CorrelationID' => $correlationId
            ]);

            // Required query parameters must be in the URL
            $queryParams = [
                'includeAvailability' => 'true',
                'includePricing' => 'true',
                'includeProductAttributes' => 'true'
            ];

            $response = $this->request(
                'POST',
                '/sandbox/resellers/v6/catalog/priceandavailability?' . http_build_query($queryParams),
                ['products' => $params['products']]
            );


            return $this->handleResponse($response);
        });
    }

    public function getOrder(string $orderId): array
    {
        return $this->executeWithRetry(function () use ($orderId) {
            $response = $this->request('GET', "orders/{$orderId}");
            return $this->handleResponse($response);
        });
    }

    public function getOrders(array $filters = []): array
    {
        return $this->executeWithRetry(function () use ($filters) {
            $response = $this->request('GET', 'orders', $filters);
            return $this->handleResponse($response);
        });
    }

    public function updateOrderStatus(string $orderId, string $status): array
    {
        return $this->executeWithRetry(function () use ($orderId, $status) {
            $response = $this->request('PUT', "orders/{$orderId}/status", [
                'status' => $status
            ]);
            return $this->handleResponse($response);
        });
    }

    protected function getAccessToken(): string
    {
        $cacheKey = 'ingram_micro_token_' . $this->supplier->id;
        
        try {
            // Check for existing valid token
            if ($cachedToken = cache()->get($cacheKey)) {
                return $cachedToken;
            }

            return $this->executeWithRetry(function () use ($cacheKey) {
                $response = Http::asForm()
                
                    ->post($this->baseUrl . '/oauth/oauth30/token', [
                        'grant_type' => 'client_credentials',
                        'client_id' => decrypt($this->apiKey),
                        'client_secret' => decrypt($this->apiSecret),
                        'scope' => 'read'
                    ]);

                if ($response->failed()) {
                    $errorBody = $response->body();
                    $statusCode = $response->status();
                    
                    // Log the error for debugging
                    logger()->error('Ingram Micro token request failed', [
                        'status' => $statusCode,
                        'error' => $errorBody,
                        'supplier_id' => $this->supplier->id
                    ]);

                    throw new ApiException(
                        "Failed to obtain access token (HTTP {$statusCode}): {$errorBody}",
                        $statusCode
                    );
                }

                $data = $response->json();
                if (!isset($data['access_token']) || !isset($data['expires_in'])) {
                    throw new ApiException('Invalid token response format: missing required fields');
                }

                // Cache the token for slightly less than its expiration time
                $expiresIn = (int) $data['expires_in'];
                $cacheTime = $expiresIn > 60 ? $expiresIn - 60 : $expiresIn;
                
                cache()->put($cacheKey, $data['access_token'], now()->addSeconds($cacheTime));
                
                return $data['access_token'];
            });
        } catch (\Exception $e) {
            // Clear the cached token in case it's invalid
            cache()->forget($cacheKey);
            
            // Log the detailed error
            logger()->error('Failed to obtain Ingram Micro access token', [
                'error' => $e->getMessage(),
                'supplier_id' => $this->supplier->id,
                'trace' => $e->getTraceAsString()
            ]);

            throw new ApiException(
                'Failed to obtain access token: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    protected function handleResponse($response): array
    {
        $data = parent::handleResponse($response);

        // Handle Ingram Micro specific response format
        if (isset($data['status']) && $data['status'] !== 'success') {
            throw new ApiException(
                $data['message'] ?? 'Unknown error occurred',
                $data['code'] ?? 500
            );
        }

        return $data['data'] ?? $data;
    }
}