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

class AmazonApiClient
{
    use HandlesRateLimiting;

    private Destination $destination;
    private ConnectionPair $connectionPair;
    private string $region;
    private bool $sandbox;
    private array $credentials;

    // Hardcoded IDs
    private const SELLER_ID = 'A3S94RDJXZL1YY';
    private const MARKETPLACE_ID = 'A2EUQ1WTGCTBG2'; // Canada marketplace ID

    // API Versions
    private const CATALOG_API_VERSION = '2022-04-01';
    private const LISTINGS_API_VERSION = '2021-08-01';
    private const FEEDS_API_VERSION = '2021-06-30';

    // API Endpoints
    private const ENDPOINTS = [
        'US' => [
            'prod' => 'https://sellingpartnerapi-na.amazon.com',
            'sandbox' => 'https://sandbox.sellingpartnerapi-na.amazon.com'
        ],
        'CA' => [
            'prod' => 'https://sellingpartnerapi-na.amazon.com', // Canada uses North America endpoint
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
        $this->destination = $connectionPair->destination;

        if ($this->destination->type !== 'amazon') {
            throw new \InvalidArgumentException('Destination must be of type amazon');
        }

        $this->region = $this->destination->region ?? 'CA'; // Default to CA region
        if (!isset(self::ENDPOINTS[$this->region])) {
            throw new \InvalidArgumentException("Unsupported region: {$this->region}. Supported regions: " . implode(', ', array_keys(self::ENDPOINTS)));
        }

        $this->sandbox = (bool)($this->connectionPair->settings['sandbox'] ?? false);
        $this->credentials = $this->destination->credentials ?? [];
    }

    /**
     * Get the base URL for the Amazon SP-API
     */
    private function getBaseUrl(): string
    {
        return self::ENDPOINTS[$this->region][$this->sandbox ? 'sandbox' : 'prod'];
    }

    /**
     * Get the LWA (Login with Amazon) access token
     */
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

        return $response->json('access_token');
    }

    /**
     * Sign a request with AWS Signature V4
     */
    private function signRequest(string $method, string $path, array $query = [], $body = null): array
    {
        $url = $this->getBaseUrl() . $path . ($query ? '?' . http_build_query($query) : '');

        $headers = [
            'host' => parse_url($this->getBaseUrl(), PHP_URL_HOST),
            'user-agent' => 'alterity/1.0 (Language=PHP)',
            'x-amz-access-token' => $this->getAccessToken(),
            'content-type' => 'application/json; charset=UTF-8',
        ];

        $payload = $body === null ? '' : (is_string($body) ? $body : json_encode($body));
        $request = new Request($method, $url, $headers, $payload);

        $signer = new SignatureV4('execute-api', $this->region);
        $creds = new Credentials(
            $this->destination->api_key,
            $this->destination->api_secret
        );

        $signed = $signer->signRequest($request, $creds);

        return [
            'url' => (string) $signed->getUri(),
            'headers' => $signed->getHeaders(),
        ];
    }

    /**
     * Get the marketplace ID for the current region
     */
    private function getMarketplaceId(): string
    {
        return self::MARKETPLACE_ID;  // Always return Canada marketplace ID
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
     * Generate the full SKU using the connection pair's prefix and the product's SKU
     */
    private function getPrefixedSku(Product $product): string
    {
        $prefix = $this->connectionPair->sku_prefix ?? '';
        $productSku = $product->sku ?? '';

        if (empty($prefix) || empty($productSku)) {
            Log::warning('Missing SKU prefix or product SKU', [
                'connection_pair_id' => $this->connectionPair->id,
                'prefix' => $prefix,
                'product_sku' => $productSku,
                'product_id' => $product->id
            ]);
        }

        return trim($prefix . $productSku);
    }

    /**
     * Update a product listing
     */
    public function updateListing(Product $product): bool
    {
        $path = sprintf(
            "/listings/%s/items/%s/%s",
            self::LISTINGS_API_VERSION,
            self::SELLER_ID,
            $this->getPrefixedSku($product)
        );

        $payload = $this->buildListingsItemsPayload($product, $product->category);

        $signed = $this->signRequest('PUT', $path, [], $payload);
        $response = Http::withHeaders($signed['headers'])->put($signed['url'], $payload);

        if ($response->failed()) {
            Log::error('Listing update failed', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'product_id' => $product->id,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            return false;
        }

        return in_array($response->status(), [200, 202], true);
    }

    /**
     * Create a feed document for bulk updates
     */


     /**
 * Create a feed document for bulk updates
 */
/**
 * Create a feed document for bulk updates
 */


 protected function createFeedDocument(string $feedType, bool $gzip): array
    {
        $this->checkRateLimit('createFeedDocument');

        $payload = [
            'contentType' => $feedType === 'POST_FLAT_FILE_LISTINGS_FEED'
                ? 'text/tab-separated-values; charset=UTF-8'
                : 'application/xml; compressed=gzip'
        ];

        $response = Http::post(
            'https://sellingpartnerapi-na.amazon.com/feeds/2021-06-30/documents',
            $payload
        );

        if ($response->failed()) {
            Log::error('Failed to create feed document', [
                'feed_type' => $feedType,
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to create feed document: ' . $response->body());
        }

        $data = $response->json();
        Log::info('Feed document created', [
            'connection_pair_id' => $this->connectionPair->id,
            'feed_document_id' => $data['feedDocumentId'],
            'url' => $data['url']
        ]);

        return [
            'feed_document_id' => $data['feedDocumentId'],
            'url' => $data['url']
        ];
    }



// private function createFeedDocument(string $contentType, bool $gzip = false): array
// {
//     $this->checkRateLimit('createFeedDocument');
//     if (!$this->consumeBurstToken('createFeedDocument')) {
//         Log::warning('Rate limit exceeded for feed document creation', [
//             'connection_pair_id' => $this->connectionPair->id,
//         ]);
//         throw new \Exception('Rate limit exceeded for feed document creation');
//     }

//     $path = "/feeds/" . self::FEEDS_API_VERSION . "/documents";
//     $effectiveContentType = $gzip ? $contentType . '; compressed=gzip' : $contentType;
//     $payload = ['contentType' => $effectiveContentType];

//     Log::info('Creating feed document', [
//         'connection_pair_id' => $this->connectionPair->id,
//         'path' => $path,
//         'payload' => $payload,
//         'gzip' => $gzip,
//     ]);

//     $signed = $this->signRequest('POST', $path, [], $payload);
//     $response = Http::withHeaders($signed['headers'])->post($signed['url'], $payload);

//     if ($response->failed()) {
//         Log::error('Feed document creation failed', [
//             'connection_pair_id' => $this->connectionPair->id,
//             'destination_id' => $this->destination->id,
//             'response' => $response->json(),
//             'status' => $response->status(),
//             'response_body' => $response->body(),
//         ]);
//         throw new \Exception('Failed to create feed document: ' . $response->body());
//     }

//     $responseData = $response->json();
//     Log::info('Feed document created', [
//         'connection_pair_id' => $this->connectionPair->id,
//         'feed_document_id' => $responseData['feedDocumentId'],
//         'url' => $responseData['url'],
//     ]);

//     return $responseData;
// }

    /**
     * Upload feed content to S3 using presigned URL
     */

     /**
 * Upload feed content to S3 using presigned URL
 */
/**
 * Upload feed content to S3 using presigned URL
 */
/**
 * Upload feed content to S3 using presigned URL
 */



    /**
     * Create a feed for bulk updates
     */

     protected function uploadFeedContent(string $url, string $content, bool $gzipEnabled): int
    {
        $headers = [
            'Content-Type' => 'text/tab-separated-values; charset=UTF-8'
        ];
        $body = $content;

        if ($gzipEnabled) {
            $headers['Content-Encoding'] = 'gzip';
            $body = gzencode($content, 9);
            $headers['Content-Type'] = 'text/tab-separated-values; charset=UTF-8; compressed=gzip';
        }

        $contentLength = strlen($body);

        Log::info('Preparing to upload feed content', [
            'connection_pair_id' => $this->connectionPair->id,
            'url' => $url,
            'headers' => $headers,
            'content_length' => $contentLength,
            'gzip_enabled' => $gzipEnabled
        ]);

        $response = Http::withHeaders($headers)->put($url, $body);

        if ($response->failed()) {
            Log::error('Failed to upload feed content', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to upload feed content: ' . $response->body());
        }

        Log::info('Feed content uploaded successfully', [
            'connection_pair_id' => $this->connectionPair->id,
            'status' => $response->status(),
            'response_headers' => $response->headers()
        ]);

        return $contentLength;
    }


     /**
 * Create a feed for bulk updates
 */
/**
 * Create a feed for bulk updates
 */

    
 protected function submitFeed(string $feedType, string $feedDocumentId): string
    {
        $payload = [
            'feedType' => $feedType,
            'marketplaceIds' => [$this->connectionPair->marketplace_id],
            'feedDocumentId' => $feedDocumentId
        ];

        $response = Http::post(
            'https://sellingpartnerapi-na.amazon.com/feeds/2021-06-30/feeds',
            $payload
        );

        if ($response->failed()) {
            Log::error('Failed to submit feed', [
                'feed_type' => $feedType,
                'feed_document_id' => $feedDocumentId,
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to submit feed: ' . $response->body());
        }

        $data = $response->json();
        return $data['feedId'];
    }

    /**
     * Generate feed content for a product
     */
    /**
 * Generate feed content for a product
 */
/**
 * Generate feed content for a product
 */
private function generateFeedContent(Product $product): string
{
    // Validate required product fields
    if (empty($product->sku)) {
        Log::error('Product SKU is missing', [
            'product_id' => $product->id,
            'connection_pair_id' => $this->connectionPair->id
        ]);
        throw new \InvalidArgumentException('Product SKU is required for feed generation');
    }
    if (empty($product->upc)) {
        Log::error('Product UPC is missing', [
            'product_id' => $product->id,
            'connection_pair_id' => $this->connectionPair->id
        ]);
        throw new \InvalidArgumentException('Product UPC is required for feed generation');
    }

    $sku = $this->getPrefixedSku($product);
    if (empty($sku)) {
        Log::error('Generated SKU is empty', [
            'product_id' => $product->id,
            'connection_pair_id' => $this->connectionPair->id,
            'prefix' => $this->connectionPair->sku_prefix,
            'product_sku' => $product->sku
        ]);
        throw new \InvalidArgumentException('Generated SKU cannot be empty');
    }

    // Validate SKU existence
    if (!$this->validateSku($sku)) {
        Log::error('SKU does not exist in seller inventory', [
            'sku' => $sku,
            'product_id' => $product->id,
            'connection_pair_id' => $this->connectionPair->id
        ]);
        throw new \InvalidArgumentException("SKU {$sku} does not exist in seller inventory");
    }

    $feed = [
        'header' => [
            'sellerId' => self::SELLER_ID,
            'version' => '2.0',
            'feedType' => 'JSON_LISTINGS_FEED',
            'marketplaceIds' => [self::MARKETPLACE_ID],
        ],
        'messages' => [
            [
                'messageId' => '1',
                'operationType' => 'UPDATE',
                'marketplaceIds' => [self::MARKETPLACE_ID],
                'sku' => $sku,
                'productType' => 'PRODUCT',
                'attributes' => [
                    'externally_assigned_product_identifier' => [
                        [
                            'marketplaceId' => self::MARKETPLACE_ID,
                            'type' => 'UPC',
                            'value' => $product->upc,
                        ]
                    ],
                    'brand' => [
                        [
                            'marketplaceId' => self::MARKETPLACE_ID,
                            'value' => $product->brand ?? 'Unknown',
                        ]
                    ],
                    'item_name' => [
                        [
                            'marketplaceId' => self::MARKETPLACE_ID,
                            'value' => $product->name ?? 'Unnamed Product',
                        ]
                    ],
                    'description' => [
                        [
                            'marketplaceId' => self::MARKETPLACE_ID,
                            'value' => $product->description ?? '',
                        ]
                    ],
                    'condition_type' => [
                        [
                            'marketplaceId' => self::MARKETPLACE_ID,
                            'value' => 'new_new',
                        ]
                    ],
                ],
            ],
        ],
    ];

    $feedJson = json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('Failed to encode feed content', [
            'product_id' => $product->id,
            'connection_pair_id' => $this->connectionPair->id,
            'json_error' => json_last_error_msg()
        ]);
        throw new \Exception('Failed to encode feed content: ' . json_last_error_msg());
    }

    Log::debug('Generated feed content', [
        'product_id' => $product->id,
        'connection_pair_id' => $this->connectionPair->id,
        'sku' => $sku,
        'feed_content' => $feedJson
    ]);

    return $feedJson;
}
    /**
     * Validate if the SKU exists in seller inventory
     */

     /**
 * Validate if an SKU exists in the seller's inventory
 */
private function validateSku(string $sku): bool
{
    $path = sprintf("/listings/%s/items/%s/%s", self::LISTINGS_API_VERSION, self::SELLER_ID, $sku);
    $query = ['marketplaceIds' => self::MARKETPLACE_ID];

    Log::info('Validating SKU', [
        'sku' => $sku,
        'connection_pair_id' => $this->connectionPair->id,
        'path' => $path,
    ]);

    $signed = $this->signRequest('GET', $path, $query);
    $response = Http::withHeaders($signed['headers'])->get($signed['url']);

    if ($response->successful()) {
        Log::info('SKU validated successfully', [
            'sku' => $sku,
            'connection_pair_id' => $this->connectionPair->id,
        ]);
        return true;
    }

    Log::warning('SKU validation failed', [
        'sku' => $sku,
        'connection_pair_id' => $this->connectionPair->id,
        'response' => $response->json(),
        'status' => $response->status(),
    ]);
    return false;
}

    /**
     * Build the payload for Listings Items API
     */

    private function buildListingsItemsPayload(Product $product, string $product_category): array
    {
        // Get catalog data if available
        $catalogData = $product->catalog_data ?? [];
        $catalogSummary = $catalogData['summaries'][0] ?? [];
        $asin = $catalogData['items'][0]['asin'] ?? null;

        if (!$asin) {
            throw new \Exception("No ASIN found in catalog data");
        }

        // Get the product type using ASIN
        $productType = $this->getProductTypeByAsin($asin);
        if (!$productType) {
            throw new \Exception("Could not determine product type for ASIN: {$asin}");
        }

        return [
            'productType' => $productType,
            'patches' => [
                [
                    'op' => 'PUSH',
                    'path' => '/attributes',
                    'value' => [
                        'externally_assigned_product_identifier' => [[
                            'type' => 'UPC',
                            'value' => $product->upc,
                        ]],
                        'brand_name' => [['value' => $product->brand]],
                        'item_name' => [['value' => $product->name]],
                        'product_description' => [['value' => $product->description]],
                        'manufacturer' => [['value' => $product->manufacturer]],
                        'model_number' => [['value' => $catalogSummary['modelNumber'] ?? '']],
                        'part_number' => [['value' => $catalogSummary['partNumber'] ?? '']],
                        'color' => [['value' => $catalogSummary['color'] ?? '']],
                        'size' => [['value' => $catalogSummary['size'] ?? '']],
                        'browse_classification' => [[
                            'display_name' => $catalogSummary['browseClassification']['displayName'] ?? '',
                            'classification_id' => $catalogSummary['browseClassification']['classificationId'] ?? '',
                        ]],
                        'item_condition' => [[
                            'condition_type' => 'new_new',
                            'condition_note' => 'Brand New',
                        ]],
                        'package_quantity' => [[
                            'value' => $catalogSummary['packageQuantity'] ?? 1,
                        ]]
                    ],
                ],
            ],
        ];
    }

    /**
     * Add an existing catalog item to seller catalog
     */
    public function addToSellerCatalog(Product $product, float $sellerPrice, int $quantity, string $product_category): bool
    {
        // Step 1: Get the ASIN from catalog data
        $catalogData = $product->catalog_data ?? [];
        $catalogItem = $catalogData['items'][0] ?? null;

        Log::info('Adding product to seller catalog', [
            'product' => $product,
            'sellerPrice' => $sellerPrice,
            'quantity' => $quantity,
            'catalog_data' => $catalogData
        ]);

        if (!$catalogItem || empty($catalogItem['asin'])) {
            throw new \Exception("No ASIN found in catalog data");
        }

        $asin = $catalogItem['asin'];

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

        // If the product category is "Displays", use it as a keyword
        if ($product_category == "Displays") {
            $keywords = "Monitors";
            
        } else {
            $keywords = $product_category;
        }

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
     * Bulk Update
     */

/**
 * Bulk Update
 */

 public function updateBulkListingPricesWithFeed(array $items): array
    {
        try {
            // Step 1: Create feed document
            $feedDocument = $this->createFeedDocument('POST_FLAT_FILE_LISTINGS_FEED', true);
            $feedDocumentId = $feedDocument['feed_document_id'];
            $url = $feedDocument['url'];

            // Step 2: Generate TSV feed content
            $tsvContent = "sku\tstandard_price\tquantity\n";
            foreach ($items as $item) {
                $sku = htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8');
                $price = number_format((float) $item['sellerPrice'], 2, '.', '');
                $quantity = (int) $item['quantity'];
                $tsvContent .= "{$sku}\t{$price}\t{$quantity}\n";
            }

            Log::debug('TSV feed content generated', [
                'feed_document_id' => $feedDocumentId,
                'content_preview' => substr($tsvContent, 0, 500),
                'connection_pair_id' => $this->connectionPair->id
            ]);

            // Step 3: Upload feed content
            $contentLength = $this->uploadFeedContent($url, $tsvContent, true);

            // Step 4: Submit feed
            $feedId = $this->submitFeed('POST_FLAT_FILE_LISTINGS_FEED', $feedDocumentId);

            Log::info('Feed submitted', [
                'feed_id' => $feedId,
                'feed_document_id' => $feedDocumentId,
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

    /**
     * Get the feed result document
     *
     * @param string $resultDocumentId
     * @return array
     * @throws \Exception
     */

     public function getFeedResult(string $resultDocumentId): array
     {
         try {
             $response = Http::get($this->getFeedResultUrl($resultDocumentId));
 
             if ($response->failed()) {
                 Log::error('Failed to retrieve feed result document', [
                     'result_document_id' => $resultDocumentId,
                     'status' => $response->status(),
                     'response' => $response->body(),
                     'connection_pair_id' => $this->connectionPair->id
                 ]);
                 throw new \Exception('Failed to retrieve feed result document: ' . $response->body());
             }
 
             $content = $response->body();
             $headers = $response->headers();
             $contentLength = strlen($content);
 
             Log::debug('Raw feed result content', [
                 'result_document_id' => $resultDocumentId,
                 'content_length' => $contentLength,
                 'content_preview' => substr($content, 0, 500),
                 'headers' => $headers,
                 'connection_pair_id' => $this->connectionPair->id
             ]);
 
             libxml_use_internal_errors(true);
             $dom = new \DOMDocument();
             if (!$dom->loadXML($content)) {
                 $errors = libxml_get_errors();
                 Log::error('Invalid feed result XML', [
                     'result_document_id' => $resultDocumentId,
                     'errors' => $errors,
                     'content_preview' => substr($content, 0, 500),
                     'connection_pair_id' => $this->connectionPair->id
                 ]);
                 throw new \Exception('Invalid feed result XML: ' . json_encode($errors));
             }
 
             $xml = new \SimpleXMLElement($content);
             libxml_use_internal_errors(false);
 
             $result = $this->xmlToArray($xml);
 
             Log::info('Parsed feed result', [
                 'result_document_id' => $resultDocumentId,
                 'result' => $result,
                 'connection_pair_id' => $this->connectionPair->id
             ]);
 
             return $result;
         } catch (\Exception $e) {
             Log::error('Failed to parse feed result XML', [
                 'result_document_id' => $resultDocumentId,
                 'error' => $e->getMessage(),
                 'content_preview' => substr($content ?? '', 0, 500),
                 'connection_pair_id' => $this->connectionPair->id
             ]);
             throw new \Exception('Failed to parse feed result XML: ' . $e->getMessage());
         }
     }
 
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
         // Implement logic to retrieve the presigned URL for the result document
         // This may involve calling the Feeds API or using a cached URL
         return "https://tortuga-prod-na.s3-external-1.amazonaws.com/{$resultDocumentId}";
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
     * Submit a feed with proper feed type
     */


     /**
 * Submit a feed with proper feed type
 */


 /**
 * Submit a feed with proper feed type
 */


public function getFeedStatus(string $feedId): array
    {
        $response = Http::get("https://sellingpartnerapi-na.amazon.com/feeds/2021-06-30/feeds/{$feedId}");

        if ($response->failed()) {
            Log::error('Failed to get feed status', [
                'feed_id' => $feedId,
                'response' => $response->body(),
                'connection_pair_id' => $this->connectionPair->id
            ]);
            throw new \Exception('Failed to get feed status: ' . $response->body());
        }

        $data = $response->json();
        Log::info('Feed status', [
            'feed_id' => $feedId,
            'status' => $data['status'],
            'connection_pair_id' => $this->connectionPair->id
        ]);

        return $data;
    }

}



// /**
//  * Update inventory and price for a single product
//  */
// public function updateProductInventoryAndPrice(Product $product): bool
// {
//     $sku = $this->getPrefixedSku($product);
//     if (empty($sku)) {
//         Log::error('Generated SKU is empty', [
//             'product_id' => $product->id,
//             'connection_pair_id' => $this->connectionPair->id
//         ]);
//         throw new \InvalidArgumentException('Generated SKU cannot be empty');
//     }

//     if (!$this->validateSku($sku)) {
//         Log::error('SKU does not exist in seller inventory', [
//             'sku' => $sku,
//             'product_id' => $摸
//             'connection_pair_id' => $this->connectionPair->id
//         ]);
//         throw new \InvalidArgumentException("SKU {$sku} does not exist in seller inventory");
//     }

//     $items = [
//         [
//             'sku' => $sku,
//             'sellerPrice' => $product->price ?? 0,
//             'quantity' => $product->quantity ?? 0,
//         ]
//     ];

//     return $this->updateBulkListingPricesWithFeed($items);
// }