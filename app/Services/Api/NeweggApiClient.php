<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Services\Api\Exceptions\ApiException;

class NeweggApiClient extends AbstractApiClient
{
    protected Destination $destination;
    protected string $apiKey;
    protected string $secretKey;
    protected string $sellerId;
    protected string $marketplace;

    public function __construct(Destination $destination)
    {
        $this->destination = $destination;
        $this->marketplace = str_contains($destination->api_endpoint, '.ca') ? 'NEWEGG_CA' : 'NEWEGG_US';
        $this->baseUrl = rtrim($destination->api_endpoint, '/') . '/marketplace';
        $this->apiKey = $destination->api_key;
        $this->secretKey = $destination->api_secret;
        $this->sellerId = $destination->seller_id;
    }

    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->apiKey,
            'SecretKey' => $this->secretKey,
        ]);

        $this->setConfig([
            'verify' => true,
            'timeout' => 30,
        ]);

        $this->isInitialized = true;
    }

    public function getProduct(string $sku): array
    {
        $response = $this->request('POST', 'contentmgmt/item/inventory?sellerid=' . $this->sellerId, [
            'RequestBody' => [
                'GetInventoryRequest' => [
                    'SellerPartNumber' => [$sku]
                ]
            ]
        ]);
        return $this->handleResponse($response)['ItemList'][0] ?? [];
    }

    public function getProducts(array $skus = []): array
    {
        $requestData = [
            'RequestBody' => [
                'GetInventoryRequest' => []
            ]
        ];
        
        if (!empty($skus)) {
            $requestData['RequestBody']['GetInventoryRequest']['SellerPartNumber'] = $skus;
        }
        
        $response = $this->request('POST', 'contentmgmt/item/inventory?sellerid=' . $this->sellerId, $requestData);
        return $this->handleResponse($response)['ItemList'] ?? [];
    }

    public function updateProduct(array $productData): array
    {
        $formattedData = $this->formatProductData($productData);
        
        $response = $this->request('PUT', 'seller/inventory/item/management', [
            'RequestBody' => [
                'Item' => $formattedData
            ]
        ]);

        return $this->handleResponse($response);
    }

    public function updateInventory(string $sku, int $quantity): array
    {
        $response = $this->request('PUT', 'contentmgmt/item/inventoryandprice?sellerid=' . $this->sellerId, [
            'RequestBody' => [
                'UpdateInventoryAndPriceRequest' => [
                    'Item' => [[
                        'SellerPartNumber' => $sku,
                        'Inventory' => $quantity,
                    ]]
                ]
            ]
        ]);

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        $data = $response->json();

        if ($response->failed() || ($data['IsSuccess'] ?? true) === false) {
            throw new ApiException(
                'Newegg API error: ' . ($data['ResponseBody']['Errors'][0]['Message'] ?? 'Unknown error'),
                $response->status()
            );
        }

        return $data['ResponseBody'] ?? [];
    }

    protected function formatProductData(array $data, array $existingProduct = []): array
    {
        $isExistingItem = !empty($data['manufacturer_name']) || !empty($existingProduct);
        
        $baseData = [
            'SellerPartNumber' => $data['sku'],
            'ItemName' => $data['name'] ?? '',
            'Description' => $data['description'] ?? '',
            'UnitPrice' => (float) ($data['price'] ?? 0),
            'Inventory' => $data['quantity'] ?? 0,
            'Status' => 1, // 1 for Active
            'PacksOrSets' => 1,
            'ItemCondition' => 1, // 1 for New
            'ShippingRestriction' => 0, // 0 for No restriction
            'PackageLength' => (float) ($data['package_length'] ?? 1),
            'PackageHeight' => (float) ($data['package_height'] ?? 1),
            'PackageWidth' => (float) ($data['package_width'] ?? 1),
            'PackageWeight' => (float) ($data['package_weight'] ?? 1),
            'Condition' => $data['condition'] ?? 1, // 1 for New
            'SellerWarranty' => $data['warranty'] ?? '',
            'ManufacturerPartNumber' => $data['mpn'] ?? '',
            'UPC' => $data['upc'] ?? '',
            'ISBN' => $data['isbn'] ?? '',
            'Currency' => $this->marketplace === 'NEWEGG_CA' ? 'CAD' : 'USD',
            'ShipByNewegg' => 0, // 0 for Self-fulfilled
            'WebsiteShortTitle' => substr($data['name'] ?? '', 0, 200),
            'BulletDescription' => $data['bullet_points'] ?? [],
            'ItemImages' => array_map(function($url) {
                return ['ImageUrl' => $url];
            }, $data['images'] ?? [])
        ];

        if ($isExistingItem) {
            // Additional fields required for existing item creation feed
            $existingItemData = [
                'ManufacturerName' => $data['manufacturer_name'] ?? '',
                'ShippingR2R' => $data['shipping_r2r'] ?? 0, // 0 for No, 1 for Yes
                'ItemCategory' => $data['category'] ?? '',
                'SubCategory1' => $data['subcategory_1'] ?? '',
                'SubCategory2' => $data['subcategory_2'] ?? '',
                'SubCategory3' => $data['subcategory_3'] ?? '',
                'ItemType' => $data['item_type'] ?? '',
                'ItemGroupCode' => $data['group_code'] ?? '',
                'ItemConditionDescription' => $data['condition_description'] ?? '',
                'ItemDimension' => [
                    'ItemLength' => (float) ($data['item_length'] ?? $data['package_length'] ?? 1),
                    'ItemWidth' => (float) ($data['item_width'] ?? $data['package_width'] ?? 1),
                    'ItemHeight' => (float) ($data['item_height'] ?? $data['package_height'] ?? 1),
                    'ItemWeight' => (float) ($data['item_weight'] ?? $data['package_weight'] ?? 1),
                ],
                'ItemPackage' => [
                    'PackageLength' => (float) ($data['package_length'] ?? 1),
                    'PackageWidth' => (float) ($data['package_width'] ?? 1),
                    'PackageHeight' => (float) ($data['package_height'] ?? 1),
                    'PackageWeight' => (float) ($data['package_weight'] ?? 1),
                ],
                'CountryOfOrigin' => $data['country_of_origin'] ?? 'USA',
                'PropCode65Warning' => $data['prop_65_warning'] ?? false,
                'PropCode65WarningText' => $data['prop_65_warning_text'] ?? ''
            ];

            return array_merge($baseData, $existingItemData);
        }

        return $baseData;
    }

    public function updatePrice(string $sku, float $price): array
    {
        $response = $this->request('PUT', 'contentmgmt/item/price?sellerid=' . $this->sellerId, [
            'RequestBody' => [
                'UpdatePriceRequest' => [
                    'Item' => [[
                        'SellerPartNumber' => $sku,
                        'UnitPrice' => $price,
                    ]]
                ]
            ]
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Submit existing item creation feed to Newegg
     * This allows creating items by matching against existing items in Newegg catalog
     * using ISBN, UPC, Manufacturer Part Number, or Newegg Item Number
     */
    public function submitExistingItemCreationFeed(array $items): array
    {
        $feedData = [
            'DocumentVersion' => '2.0',
            'MessageType' => 'BatchItemCreation',
            'Item' => []
        ];

        foreach ($items as $item) {
            $itemData = [
                'SellerPartNumber' => $item['sku'],
                'Manufacturer' => $item['manufacturer'],
                'Currency' => $this->marketplace === 'NEWEGG_CA' ? 'CAD' : 'USD',
                'SellingPrice' => (float) $item['price'],
                'Shipping' => $item['shipping'] ?? 'Default',
                'Inventory' => (int) $item['quantity'],
                'ActivationMark' => $item['activation_mark'] ?? 'True',
                'ItemCondition' => $item['condition'] ?? 'New'
            ];

            // Add identifier - at least one is required
            if (!empty($item['manufacturer_part_number'])) {
                $itemData['ManufacturerPartsNumber'] = $item['manufacturer_part_number'];
            }
            if (!empty($item['upc'])) {
                $itemData['UPCOrISBN'] = $item['upc'];
            }
            if (!empty($item['newegg_item_number'])) {
                $itemData['NeweggItemNumber'] = $item['newegg_item_number'];
            }

            // Optional fields
            if (!empty($item['msrp'])) {
                $itemData['MSRP'] = (float) $item['msrp'];
            }
            if (!empty($item['map'])) {
                $itemData['MAP'] = (float) $item['map'];
            }
            if (isset($item['checkout_map'])) {
                $itemData['CheckoutMAP'] = $item['checkout_map'] ? 'True' : 'False';
            }
            if (!empty($item['warranty'])) {
                $itemData['WarrantyType'] = $item['warranty'];
            }
            if (!empty($item['return_policy'])) {
                $itemData['ReturnPolicyOverride'] = $item['return_policy'];
            }

            $feedData['Item'][] = $itemData;
        }

        $response = $this->request('POST', 'datafeedmgmt/feeds/submitfeed?sellerid=' . $this->sellerId . '&requesttype=ITEM_DATA', [
            'RequestBody' => $feedData
        ]);

        return $this->handleResponse($response);
    }

    public function getOrder(string $orderId): array
    {
        $response = $this->request('GET', 'ordermgmt/orderstatus/orders/' . $orderId . '?sellerid=' . $this->sellerId);
        return $this->handleResponse($response)['OrderInfo'] ?? [];
    }

    public function getOrders(array $filters = []): array
    {
        $requestData = [
            'RequestBody' => [
                'GetOrderStatusRequest' => []
            ]
        ];
        
        if (!empty($filters['status'])) {
            $requestData['RequestBody']['GetOrderStatusRequest']['OrderStatus'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $requestData['RequestBody']['GetOrderStatusRequest']['OrderDateFrom'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $requestData['RequestBody']['GetOrderStatusRequest']['OrderDateTo'] = $filters['date_to'];
        }
        
        $response = $this->request('POST', 'ordermgmt/orderstatus?sellerid=' . $this->sellerId, $requestData);
        
        return $this->handleResponse($response)['OrderList'] ?? [];
    }

    public function updateOrderStatus(string $orderId, string $status): array
    {
        $response = $this->request('PUT', 'ordermgmt/orderstatus/orders/' . $orderId . '?sellerid=' . $this->sellerId, [
            'RequestBody' => [
                'UpdateOrderStatusRequest' => [
                    'OrderNumber' => $orderId,
                    'OrderStatus' => $status,
                ]
            ]
        ]);

        return $this->handleResponse($response);
    }
}