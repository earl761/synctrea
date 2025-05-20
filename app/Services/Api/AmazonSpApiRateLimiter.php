<?php

namespace AppServicesApi;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AmazonSpApiRateLimiter
{
    private const RATE_LIMITS = [
        'catalog'   => ['rate' => 2, 'burst' => 20],   // 2 requests per second, burst of 20
        'inventory' => ['rate' => 2, 'burst' => 10],   // 2 requests per second, burst of 10
        'pricing'   => ['rate' => 1, 'burst' => 5],    // 1 request per second, burst of 5
        'listings'  => ['rate' => 5, 'burst' => 10],   // 5 requests per second, burst of 10
        'feeds'     => ['rate' => 0.0667, 'burst' => 2], // 1 request per 15 seconds, burst of 2
    ];

    private const RESTORE_RATE_MS = 1000; // 1 second in milliseconds

    public function acquireToken(string $apiType): bool
    {
        $limits = self::RATE_LIMITS[$apiType] ?? ['rate' => 1, 'burst' => 5];
        $key = "amazon_sp_api_{$apiType}_tokens";

        return Cache::lock($key, 10)->block(5, function () use ($key, $limits) {
            $tokens = Cache::get($key, $limits['burst']);
            
            if ($tokens > 0) {
                Cache::put($key, $tokens - 1, now()->addSeconds(ceil(1 / $limits['rate'])));
                return true;
            }

            Log::warning("Rate limit exceeded for Amazon SP-API {$key}");
            return false;
        });
    }

    public function waitForToken(string $apiType, int $maxWaitMs = 5000): bool
    {
        $startTime = microtime(true) * 1000;
        $waited = 0;

        while ($waited < $maxWaitMs) {
            if ($this->acquireToken($apiType)) {
                return true;
            }

            usleep(100000); // Wait 100ms
            $waited = (microtime(true) * 1000) - $startTime;
        }

        return false;
    }

    public function releaseToken(string $apiType): void
    {
        $key = "amazon_sp_api_{$apiType}_tokens";
        $limits = self::RATE_LIMITS[$apiType] ?? ['rate' => 1, 'burst' => 5];

        Cache::lock($key, 10)->block(5, function () use ($key, $limits) {
            $tokens = Cache::get($key, 0);
            if ($tokens < $limits['burst']) {
                Cache::put($key, $tokens + 1, now()->addSeconds(ceil(1 / $limits['rate'])));
            }
        });
    }
}