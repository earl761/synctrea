<?php

namespace App\Services\Api;

use App\Services\Api\Contracts\ApiClient;
use App\Services\Api\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

abstract class AbstractApiClient implements ApiClient
{
    protected string $baseUrl;
    protected array $headers = [];
    protected array $config = [];
    protected bool $isInitialized = false;

    /**
     * Make an authenticated API request
     */
    protected function request(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = []
    ): Response {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $url = $this->buildUrl($endpoint);
        $headers = array_merge($this->headers, $headers);

        $startTime = microtime(true);
        Log::info('Starting API request', [
            'url' => $url,
            'method' => $method,
            'endpoint' => $endpoint
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->config['timeout'] ?? 30)
                ->connectTimeout($this->config['connect_timeout'] ?? 10)
                ->retry(3, 100)
                ->withOptions($this->config)
                ->{strtolower($method)}($url, $data);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            if (!$response->successful()) {
                Log::error('Full API error response: ' . $response->body());
                throw new ApiException(
                    'API request failed: ' . $response->body(),
                    $response->status(),
                    ['response' => $response->json()]
                );
            }

            Log::info('API request successful', [
                'url' => $url,
                'method' => $method,
                'status' => $response->status(),
                'execution_time' => $executionTime
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('API request failed', [
                'url' => $url,
                'method' => $method,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw new ApiException(
                'API request failed: ' . $e->getMessage(),
                $e->getCode() ?: 500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Build the full URL for an API endpoint
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Set request headers
     */
    protected function setHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Set client configuration options
     */
    protected function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Handle API response
     */
    protected function handleResponse(Response $response): array
    {
        return $response->json();
    }

    /**
     * Format error response
     */
    protected function formatError(\Exception $e): array
    {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }
}