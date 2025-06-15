<?php

namespace App\Services\Api;

use App\Models\Supplier;
use App\Services\Api\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

//class IngramMicroApiClient extends AbstractApiClient
class IngramMicroApiClient extends AbstractApiClient
{
    protected Supplier $supplier;
    protected string $apiKey;

    protected string $customerNumber;
    protected string $countryCode;
    protected string $apiSecret;
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // milliseconds
    protected int $requestsPerMinute = 60;
    protected array $requestTimestamps = [];

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
        $this->baseUrl = $supplier->api_endpoint;

        Log::info('Initializing Ingram Micro API client', [
            'supplier_id' => $supplier,
            'api_endpoint' => $this->baseUrl
        ]);
        // Validate that API credentials are set
        if (empty($supplier->api_key) || empty($supplier->api_secret)) {
            throw new ApiException('API credentials are not configured for this supplier');
        }

        try {
           
            $this->apiKey = $supplier->api_key;
            $this->apiSecret = $supplier->api_secret;
            $this->customerNumber = $supplier->customer_number;
            $this->countryCode = $supplier->country_code ;

        } catch (\Exception $e) {
            Log::error('Failed to initialize IngramMicro API credentials', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage()
            ]);
            throw new ApiException('Failed to initialize API credentials: ' . $e->getMessage());
        }

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
            'pageSize' => 100,
            'type' => 'IM::any',
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
                'IM-CustomerNumber' => $this->customerNumber,
                'IM-CorrelationID' => bin2hex(random_bytes(16)),
                'IM-CountryCode' => $this->countryCode,
            ]);

            try {
                $response = $this->request('GET', 'resellers/v6/catalog', $queryParams);
                
                Log::info('Ingram Micro API response:', [
                    'status' => $response->status(),
                    'body' => $response,
                    'page' => $queryParams['pageNumber'],
                    'pageSize' => $queryParams['pageSize']
                ]);

                $data = $this->handleResponse($response);
                
                // Handle empty response cases
                if (empty($data['catalog'])) {
                    Log::info('No more catalog items to process', [
                        'page' => $queryParams['pageNumber'],
                        'pageSize' => $queryParams['pageSize']
                    ]);
                    return [
                        'catalog' => [],
                        'recordsFound' => $data['recordsFound'] ?? 0,
                        'isComplete' => true
                    ];
                }
                
                // Add completion flag
                $data['isComplete'] = false;
                if (isset($data['recordsFound']) && 
                    $queryParams['pageNumber'] * $queryParams['pageSize'] >= $data['recordsFound']) {
                    $data['isComplete'] = true;
                }
                
                return $data;
            } catch (ApiException $e) {
                // Handle 404 or no content responses as completion
                if ($e->getCode() === 404 || $e->getCode() === 204) {
                    Log::info('Catalog sync completed - no more items', [
                        'page' => $queryParams['pageNumber']
                    ]);
                    return [
                        'catalog' => [],
                        'recordsFound' => 0,
                        'isComplete' => true
                    ];
                }
                throw $e;
            }
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

        // Ensure each product has exactly one valid identifier type
        foreach ($params['products'] as $index => $product) {
            $identifiers = array_filter([
                'ingramPartNumber' => !empty($product['ingramPartNumber']),
                'vendorPartNumber' => !empty($product['vendorPartNumber']),
                'upc' => !empty($product['upc'])
            ]);

            if (count($identifiers) !== 1) {
                throw new ApiException(
                    sprintf(
                        'Product at index %d must have exactly one identifier type (ingramPartNumber, vendorPartNumber, or upc) with a non-empty value',
                        $index
                    ),
                    400
                );
            }
        }

        return $this->executeWithRetry(function () use ($params) {
            // Generate a unique 32-character correlation ID
            $correlationId = bin2hex(random_bytes(16));

            // Set required headers for the API request
            $this->setHeaders([
                'IM-CustomerNumber' =>  $this->supplier->customer_number,
                'IM-CountryCode' => $this->supplier->country_code ,
                'IM-CorrelationID' => $correlationId,
            ]);

            // Required query parameters must be in the URL
            $queryParams = [
                'includeAvailability' => 'true',
                'includePricing' => 'true',
                'includeProductAttributes' => 'true'
            ];


            $response = $this->request(
                'POST',
                'resellers/v6/catalog/priceandavailability?' . http_build_query($queryParams),
                ['products' => $params['products']]
            );


            $result = $this->handleResponse($response);

            // Log any products with null productStatusCode or productStatusMessage
            if (is_array($result)) {
                foreach ($result as $item) {
                    if ((isset($item['productStatusCode']) && is_null($item['productStatusCode'])) ||
                        (isset($item['productStatusMessage']) && is_null($item['productStatusMessage']))) {
                        Log::warning('Ingram Micro product returned null status code or message', [
                            'item' => $item,
                            'request' => $params
                        ]);
                    }
                }
            }

            return $result;
        });
    }

    public function getProductDetails(string $sku): array
    {
        // Add delay between API calls (500ms)
        usleep(500000);
        
        return $this->executeWithRetry(function () use ($sku) {
            // Generate a unique 32-character correlation ID
            $correlationId = bin2hex(random_bytes(16));

            // Set required headers for the API request
            $this->setHeaders([
                'IM-CustomerNumber' => $this->supplier->customer_number,
                'IM-CountryCode' => $this->supplier->country_code,
                'IM-CorrelationID' => $correlationId,
            ]);

            $response = $this->request('GET', "resellers/v6/catalog/details/{$sku}");
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
        try {
            // Validate encrypted credentials exist
            if (empty($this->apiKey) || empty($this->apiSecret)) {
                throw new ApiException('API credentials are not properly configured');
            }

          

            // Safely decrypt credentials
            try {
                $decryptedKey = decrypt($this->apiKey);
                $decryptedSecret = decrypt($this->apiSecret);

                Log::info('Decrypted credentials', [
                    'decrypted_key' => $decryptedKey,
                    'decrypted_secret' => $decryptedSecret
                ]);

                if (empty($decryptedKey) || empty($decryptedSecret)) {
                    throw new ApiException('Decrypted credentials are empty');
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                logger()->error('Failed to decrypt credentials', [
                    'supplier_id' => $this->supplier->id,
                    'error' => $e->getMessage()
                ]);
                throw new ApiException('Failed to decrypt API credentials: ' . $e->getMessage());
            }

            return $this->executeWithRetry(function () use ($decryptedKey, $decryptedSecret) {
                $response = Http::asForm()->post($this->baseUrl . '/oauth/oauth30/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $decryptedKey,
                    'client_secret' => $decryptedSecret,
                    'scope' => 'read'
                ]);

                if ($response->failed()) {
                    $errorBody = $response->body();
                    $statusCode = $response->status();
                    
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
                if (!isset($data['access_token'])) {
                    throw new ApiException('Invalid token response format: missing access_token field');
                }

                logger()->info('New access token created', [
                    'supplier_id' => $this->supplier->id,
                    'token_preview' => $data['access_token']
                ]);
                
                return $data['access_token'];
            });
        } catch (\Exception $e) {
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