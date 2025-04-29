<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Models\Product;
use App\Models\ConnectionPair;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use Aws\Sts\StsClient;
use GuzzleHttp\Psr7\Request;

class AmazonApiClient
{
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

        $this->region = $this->destination->region ?? 'US';
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

        $signed = $this->signRequest('GET', $path, $query);
        $response = Http::withHeaders($signed['headers'])->get($signed['url']);

        if ($response->failed()) {
            Log::error('Catalog search failed', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'upc' => $upc,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            return null;
        }

        return $response->json();
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
            $product->pivot->destination_sku
        );

        $payload = $this->buildListingsItemsPayload($product);

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
    private function createFeedDocument(string $contentType): array
    {
        $path = "/feeds/" . self::FEEDS_API_VERSION . "/documents";
        $payload = ['contentType' => $contentType];

        $signed = $this->signRequest('POST', $path, [], $payload);
        $response = Http::withHeaders($signed['headers'])->post($signed['url'], $payload);

        if ($response->failed()) {
            Log::error('Feed document creation failed', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to create feed document: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Upload feed content to S3
     */
    private function uploadFeedContent(string $url, string $content, bool $gzip = false): void
    {
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-MD5' => base64_encode(md5($content, true)),
            'x-amz-content-sha256' => hash('sha256', $content),
        ];

        if ($gzip) {
            $headers['Content-Encoding'] = 'gzip';
            $content = gzencode($content);
        }

        $response = Http::withHeaders($headers)->put($url, $content);

        if ($response->failed()) {
            Log::error('Feed content upload failed', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to upload feed content: ' . $response->body());
        }
    }

    /**
     * Create a feed for bulk updates
     */
    public function createFeed(Product $product): string
    {
        $content = $this->generateFeedContent($product);
        $docMeta = $this->createFeedDocument('application/json; charset=UTF-8');
        $this->uploadFeedContent($docMeta['url'], $content, true);

        $path = "/feeds/" . self::FEEDS_API_VERSION . "/feeds";
        $payload = [
            'feedType' => 'JSON_LISTINGS_FEED',
            'marketplaceIds' => [self::MARKETPLACE_ID],
            'inputFeedDocumentId' => $docMeta['feedDocumentId'],
        ];

        $signed = $this->signRequest('POST', $path, [], $payload);
        $response = Http::withHeaders($signed['headers'])->post($signed['url'], $payload);

        if ($response->failed()) {
            Log::error('Feed creation failed', [
                'connection_pair_id' => $this->connectionPair->id,
                'destination_id' => $this->destination->id,
                'product_id' => $product->id,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception('Failed to create feed: ' . $response->body());
        }

        return $response->json('feedId');
    }

    /**
     * Generate feed content for a product
     */
    private function generateFeedContent(Product $product): string
    {
        $feed = [
            'header' => [
                'sellerId' => self::SELLER_ID,
                'version' => '2.0',
                'feedType' => 'JSON_LISTINGS_FEED',
            ],
            'messages' => [
                [
                    'messageId' => 1,
                    'operationType' => 'PartialUpdate',
                    'sku' => $product->pivot->destination_sku,
                    'productType' => 'PRODUCT',
                    'attributes' => [
                        'externalProductId' => [[
                            'value' => $product->upc,
                            'type' => 'UPC',
                        ]],
                        'brand' => [['value' => $product->brand]],
                        'itemName' => [['value' => $product->name]],
                        'description' => [['value' => $product->description]],
                    ],
                ],
            ],
        ];

        return json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function buildListingsItemsPayload(Product $product): array
    {
        // Get catalog data if available
        $catalogData = $product->catalog_data ?? [];
        $catalogSummary = $catalogData['summaries'][0] ?? [];

        return [
            'productType' => 'PRODUCT',
            'patches' => [
                [
                    'op' => 'PUSH',
                    'path' => '/attributes',
                    'value' => [
                        'externalProductId' => [[
                            'value' => $product->upc,
                            'type' => 'UPC',
                        ]],
                        'brand' => [['value' => $product->brand]],
                        'itemName' => [['value' => $product->name]],
                        'description' => [['value' => $product->description]],
                        'manufacturer' => [['value' => $product->manufacturer]],
                        'modelNumber' => [['value' => $catalogSummary['modelNumber'] ?? '']],
                        'partNumber' => [['value' => $catalogSummary['partNumber'] ?? '']],
                        'color' => [['value' => $catalogSummary['color'] ?? '']],
                        'size' => [['value' => $catalogSummary['size'] ?? '']],
                        'itemClassification' => [['value' => $catalogSummary['itemClassification'] ?? 'BASE_PRODUCT']],
                        'websiteDisplayGroup' => [['value' => $catalogSummary['websiteDisplayGroup'] ?? '']],
                        'websiteDisplayGroupName' => [['value' => $catalogSummary['websiteDisplayGroupName'] ?? '']],
                        'browseClassification' => [[
                            'displayName' => $catalogSummary['browseClassification']['displayName'] ?? '',
                            'classificationId' => $catalogSummary['browseClassification']['classificationId'] ?? '',
                        ]],
                        'condition' => [[
                            'value' => 'new_new',
                            'subCondition' => 'new',
                        ]],
                        'packageQuantity' => [[
                            'value' => $catalogSummary['packageQuantity'] ?? 1,
                        ]],
                        'itemDimensions' => [[
                            'height' => [
                                'value' => 1,
                                'unit' => 'IN',
                            ],
                            'length' => [
                                'value' => 1,
                                'unit' => 'IN',
                            ],
                            'width' => [
                                'value' => 1,
                                'unit' => 'IN',
                            ],
                            'weight' => [
                                'value' => 1,
                                'unit' => 'LB',
                            ],
                        ]],
                    ],
                ],
            ],
        ];
    }

    /**
     * Add an existing catalog item to seller catalog
     */
    public function addToSellerCatalog(Product $product): bool
    {
        // Step 1: Get the ASIN from catalog search (already done)
        $catalogData = $product->catalog_data ?? [];
        
        // Log the catalog data for debugging
        Log::info('Catalog Data for Seller Catalog Addition', [
            'catalog_data' => $catalogData,
            'catalog_item' => $catalogData['items'][0] ?? null,
            'catalog_summary' => $catalogData['items'][0]['summaries'][0] ?? null
        ]);

        // The catalog data structure is:
        // {
        //     "numberOfResults": 1,
        //     "items": [
        //         {
        //             "asin": "B09HTCW36D",
        //             "summaries": [...]
        //         }
        //     ]
        // }
        $catalogItem = $catalogData['items'][0] ?? null;
        $catalogSummary = $catalogItem['summaries'][0] ?? null;

        if (!$catalogItem || !$catalogSummary) {
            throw new \Exception("Invalid catalog data structure");
        }

        if (empty($catalogItem['asin'])) {
            throw new \Exception("No ASIN found in catalog data");
        }

        // Step 2: Get product type definition
        $productType = 'COMPUTER'; // Use COMPUTER type for laptops according to Amazon SP-API docs
        $definitionPath = "/definitions/2020-09-01/productTypes/{$productType}";
        $query = [
            'marketplaceIds' => self::MARKETPLACE_ID,
        ];
        
        Log::info('Getting product type definition', [
            'path' => $definitionPath,
            'query' => $query,
            'marketplace_id' => self::MARKETPLACE_ID
        ]);

        $signed = $this->signRequest('GET', $definitionPath, $query);
        Log::info('Signed request for product type definition', [
            'url' => $signed['url'],
            'headers' => $signed['headers']
        ]);

        $response = Http::withHeaders($signed['headers'])->get($signed['url']);
        
        Log::info('Product type definition response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
        
        if ($response->failed()) {
            throw new \Exception("Failed to get product type definition: " . $response->body());
        }

        // Step 3: Create the listing using Listings Items API
        $path = sprintf(
            "/listings/2021-08-01/items/%s/%s",
            self::SELLER_ID,
            $product->pivot->destination_sku
        );

        Log::info('Creating listing', [
            'path' => $path,
            'seller_id' => self::SELLER_ID,
            'sku' => $product->pivot->destination_sku
        ]);

        $payload = [
            'productType' => $productType,
            'requirements' => 'LISTING',
            'marketplaceIds' => [self::MARKETPLACE_ID],
            'attributes' => [
                'externalProductId' => [
                    'value' => $catalogItem['asin'],
                    'type' => 'ASIN'
                ],
                'itemName' => [
                    'value' => $catalogSummary['itemName']
                ],
                'brand' => [
                    'value' => $catalogSummary['brand']
                ],
                'condition' => [
                    'value' => 'new_new',
                    'subCondition' => 'new'
                ],
                'itemType' => [
                    'value' => 'COMPUTER'
                ],
                'manufacturer' => [
                    'value' => $catalogSummary['manufacturer']
                ],
                'modelNumber' => [
                    'value' => $catalogSummary['modelNumber']
                ],
                'partNumber' => [
                    'value' => $catalogSummary['partNumber']
                ]
            ]
        ];

        // Log the payload for debugging
        Log::info('Payload for Seller Catalog Addition', [
            'payload' => $payload
        ]);

        // First, validate the listing
        $validatePath = $path;
        $query = [
            'requirements' => 'LISTING',
            'marketplaceIds' => self::MARKETPLACE_ID
        ];
        
        Log::info('Validating listing', [
            'path' => $validatePath,
            'query' => $query
        ]);

        $signed = $this->signRequest('PUT', $validatePath, $query, $payload);
        Log::info('Signed request for validation', [
            'url' => $signed['url'],
            'headers' => $signed['headers']
        ]);

        $validateResponse = Http::withHeaders($signed['headers'])->put($signed['url'], $payload);
        
        Log::info('Validation response', [
            'status' => $validateResponse->status(),
            'body' => $validateResponse->json()
        ]);

        if ($validateResponse->failed()) {
            $errorResponse = $validateResponse->json();
            Log::error('Failed to validate listing', [
                'response' => $errorResponse,
                'status' => $validateResponse->status()
            ]);
            throw new \Exception(
                "Failed to validate listing. Status: {$validateResponse->status()}. " .
                "Response: " . json_encode($errorResponse, JSON_PRETTY_PRINT)
            );
        }

        // If validation succeeds, create the live listing
        Log::info('Creating live listing', [
            'path' => $path
        ]);

        $signed = $this->signRequest('PUT', $path, $query, $payload);
        Log::info('Signed request for live listing', [
            'url' => $signed['url'],
            'headers' => $signed['headers']
        ]);

        $liveResponse = Http::withHeaders($signed['headers'])->put($signed['url'], $payload);
        
        Log::info('Live listing response', [
            'status' => $liveResponse->status(),
            'body' => $liveResponse->json()
        ]);

        if ($liveResponse->failed()) {
            $errorResponse = $liveResponse->json();
            Log::error('Failed to create live listing', [
                'response' => $errorResponse,
                'status' => $liveResponse->status()
            ]);
            throw new \Exception(
                "Failed to create live listing. Status: {$liveResponse->status()}. " .
                "Response: " . json_encode($errorResponse, JSON_PRETTY_PRINT)
            );
        }

        return in_array($liveResponse->status(), [200, 202], true);
    }
} 