<?php

namespace App\Services\Api\Traits;

use Illuminate\Support\Facades\Log;

trait HandlesRateLimiting
{
    protected $rateLimits = [
        'createFeedDocument' => [
            'rate' => 0.0083, // 1 feed every 120 seconds for POST_FLAT_FILE_LISTINGS_FEED
            'burst' => 15,
            'tokens' => 15,
            'last_request' => null,
        ],
        'submitFeed' => [
            'rate' => 0.0083, // Same as createFeedDocument
            'burst' => 15,
            'tokens' => 15,
            'last_request' => null,
        ],
        'getFeedStatus' => [
            'rate' => 2.0, // 2 req/s per Amazon SP-API docs
            'burst' => 15,
            'tokens' => 15,
            'last_request' => null,
        ],
    ];

    protected function checkRateLimit(string $operation): void
    {
        if (!isset($this->rateLimits[$operation])) {
            Log::warning('No rate limit defined for operation', [
                'operation' => $operation,
                'connection_pair_id' => $this->connectionPair->id
            ]);
            return;
        }

        $config = &$this->rateLimits[$operation];
        $now = microtime(true);

        if ($config['last_request'] === null) {
            $config['last_request'] = $now;
        }

        $elapsed = $now - $config['last_request'];
        $newTokens = min($config['burst'], $config['tokens'] + ($elapsed * $config['rate']));
        $config['tokens'] = $newTokens;

        Log::debug('Rate limit token refill', [
            'operation' => $operation,
            'elapsed' => $elapsed,
            'new_tokens' => $newTokens,
            'tokens' => $config['tokens'],
            'last_request' => $config['last_request'],
            'connection_pair_id' => $this->connectionPair->id
        ]);

        if ($config['tokens'] < 1) {
            $waitTime = (1 - $config['tokens']) / $config['rate'];
            $waitTime = min($waitTime, 120); // Cap at 2 minutes
            Log::info("Rate limiting {$operation}", [
                'sleep_time' => $waitTime,
                'operation' => $operation,
                'tokens' => $config['tokens'],
                'connection_pair_id' => $this->connectionPair->id
            ]);
            usleep($waitTime * 1000000);
        }

        $config['tokens'] = max(0, $config['tokens'] - 1);
        $config['last_request'] = $now;
    }

    protected function consumeBurstToken(string $operation): bool
    {
        if (!isset($this->rateLimits[$operation])) {
            return true;
        }

        $this->checkRateLimit($operation);
        return $this->rateLimits[$operation]['tokens'] >= 1;
    }
}