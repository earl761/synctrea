<?php

namespace App\Services\Api\Contracts;

interface ApiClient
{
    /**
     * Initialize the API client with necessary credentials
     */
    public function initialize(): void;

    /**
     * Get product information from the API
     */
    public function getProduct(string $sku): array;

    /**
     * Get multiple products from the API
     */
    public function getProducts(array $skus = []): array;

    /**
     * Update product information in the API
     */
    public function updateProduct(array $productData): array;

    /**
     * Update inventory levels in the API
     */
    public function updateInventory(string $sku, int $quantity): array;

    /**
     * Update pricing in the API
     */
    public function updatePrice(string $sku, float $price): array;

    /**
     * Get order information from the API
     */
    public function getOrder(string $orderId): array;

    /**
     * Get multiple orders from the API
     */
    public function getOrders(array $filters = []): array;

    /**
     * Update order status in the API
     */
    public function updateOrderStatus(string $orderId, string $status): array;
}