<?php

namespace App\Jobs;

use App\Models\ConnectionPair;
use App\Models\AmazonFeed;
use App\Services\Api\AmazonApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAmazonFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 600; // 10 minutes

    protected string $feedId;
    protected int $connectionPairId;
    protected int $retryDelay = 30; // seconds

    public function __construct(string $feedId, int $connectionPairId)
    {
        $this->feedId = $feedId;
        $this->connectionPairId = $connectionPairId;
    }

    public function handle()
    {
        $connectionPair = ConnectionPair::findOrFail($this->connectionPairId);
        $client = new AmazonApiClient($connectionPair);

        $maxAttempts = 20; // Maximum number of status checks
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                $status = $client->getFeedStatus($this->feedId);
                Log::info('Amazon Feed Status', [
                    'feed_id' => $this->feedId,
                    'status' => $status['processingStatus'],
                    'attempt' => $attempts + 1
                ]);

                if (in_array($status['processingStatus'], ['DONE', 'CANCELLED', 'FATAL'])) {
                    // Process feed results if available
                    if (isset($status['resultFeedDocumentId'])) {
                        $results = $client->getFeedResult($status['resultFeedDocumentId']);
                        $this->processFeedResults($results);
                    }

                    // Update feed status in database
                    $this->updateFeedStatus($status);
                    break;
                }

                $attempts++;
                if ($attempts < $maxAttempts) {
                    sleep($this->retryDelay);
                }

            } catch (\Exception $e) {
                Log::error('Error processing Amazon feed', [
                    'feed_id' => $this->feedId,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts + 1
                ]);

                if ($attempts >= $maxAttempts - 1) {
                    throw $e;
                }

                sleep($this->retryDelay);
                $attempts++;
            }
        }
    }

    protected function processFeedResults(array $results)
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($results as $result) {
            if ($result['status'] === 'SUCCESS') {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = [
                    'message_id' => $result['messageId'] ?? 'unknown',
                    'error_code' => $result['errorCode'] ?? 'unknown',
                    'error_message' => $result['errorMessage'] ?? 'unknown'
                ];
                
                Log::warning('Feed processing error', [
                    'feed_id' => $this->feedId,
                    'message_id' => $result['messageId'] ?? 'unknown',
                    'error_code' => $result['errorCode'] ?? 'unknown',
                    'error_message' => $result['errorMessage'] ?? 'unknown'
                ]);
            }
        }

        // Update feed record with results
        AmazonFeed::where('feed_id', $this->feedId)
            ->where('connection_pair_id', $this->connectionPairId)
            ->update([
                'result_summary' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'errors' => $errorCount,
                ],
                'errors' => $errors
            ]);

        Log::info('Feed processing completed', [
            'feed_id' => $this->feedId,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }

    protected function updateFeedStatus(array $status)
    {
        AmazonFeed::where('feed_id', $this->feedId)
            ->where('connection_pair_id', $this->connectionPairId)
            ->update([
                'processing_status' => $status['processingStatus'],
                'processing_start_time' => $status['processingStartTime'] ?? null,
                'processing_end_time' => $status['processingEndTime'] ?? null,
                'result_feed_document_id' => $status['resultFeedDocumentId'] ?? null,
            ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Amazon feed processing job failed', [
            'feed_id' => $this->feedId,
            'connection_pair_id' => $this->connectionPairId,
            'error' => $exception->getMessage()
        ]);
    }
}