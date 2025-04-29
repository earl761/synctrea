<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDestinations extends Command
{
    protected $signature = 'sync:destinations';
    protected $description = 'Sync products to all active destinations';

    public function handle()
    {
        $this->info('Starting destination sync...');

        try {
            $destinations = Destination::where('is_active', true)->get();

            foreach ($destinations as $destination) {
                $this->info("Syncing destination: {$destination->name}");

                try {
                    // Create a sync log entry
                    $syncLog = SyncLog::create([
                        'destination_id' => $destination->id,
                        'type' => 'destination_sync',
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);

                    // TODO: Implement the actual API call to the destination
                    // This is where you would make the API request using the destination's credentials
                    // and process the response

                    // For now, we'll just log a success message
                    $syncLog->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'message' => 'Sync completed successfully',
                    ]);

                    $this->info("Successfully synced destination: {$destination->name}");
                } catch (\Exception $e) {
                    // Log the error and update the sync log
                    $syncLog->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'message' => $e->getMessage(),
                    ]);

                    Log::error("Error syncing destination {$destination->name}: " . $e->getMessage());
                    $this->error("Error syncing destination {$destination->name}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in destination sync command: ' . $e->getMessage());
            $this->error('Error in destination sync command: ' . $e->getMessage());
            return 1;
        }

        $this->info('Destination sync completed!');
        return 0;
    }
}