<?php

namespace App\Services\Api;

use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DHApiClient
{
    protected Supplier $supplier;
    protected string $baseUrl;
    protected array $headers;

    public function __construct(Supplier $supplier)
    {   
        $this->supplier = $supplier;
        $this->baseUrl = $supplier->api_endpoint;
        $this->headers = [
            'Authorization' => 'Bearer ' . $supplier->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function authenticate()
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->post($this->baseUrl . '/auth', [
                    'client_id' => $this->supplier->credentials['client_id'],
                    'client_secret' => $this->supplier->credentials['client_secret'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->supplier->update([
                    'credentials' => array_merge(
                        $this->supplier->credentials,
                        ['access_token' => $data['access_token']]
                    )
                ]);
                return true;
            }

            Log::error('D&H Authentication failed', [
                'supplier_id' => $this->supplier->id,
                'response' => $response->json()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('D&H Authentication error', [
                'supplier_id' => $this->supplier->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getProducts(array $params = [])
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get($this->baseUrl . '/products', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('D&H Get Products failed', [
                'supplier_id' => $this->supplier->id,
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('D&H Get Products error', [
                'supplier_id' => $this->supplier->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getInventory(array $skus)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->post($this->baseUrl . '/inventory', [
                    'skus' => $skus
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('D&H Get Inventory failed', [
                'supplier_id' => $this->supplier->id,
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('D&H Get Inventory error', [
                'supplier_id' => $this->supplier->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getPricing(array $skus)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->post($this->baseUrl . '/pricing', [
                    'skus' => $skus
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('D&H Get Pricing failed', [
                'supplier_id' => $this->supplier->id,
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('D&H Get Pricing error', [
                'supplier_id' => $this->supplier->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}