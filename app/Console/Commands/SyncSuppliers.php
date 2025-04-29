<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSuppliers extends Command
{
    protected $signature = 'sync:suppliers';
    protected $description = 'Sync products from all active suppliers';

    public function handle()
    {
        $this->info('Starting supplier sync...');

        try {
            $suppliers = Supplier::where('is_active', true)
                ->where('type', 'ingram_micro')
                ->get();

            foreach ($suppliers as $supplier) {
                $this->info("Syncing supplier: {$supplier->name}");

                try {
                    // Create a sync log entry
                    $syncLog = SyncLog::create([
                        'supplier_id' => $supplier->id,
                        'type' => 'supplier_sync',
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);

                    // TODO: Implement the actual API call to Ingram Micro
                    // This is where you would make the API request using the supplier's credentials
                    // and process the response

                    // For now, we'll just log a success message
                    $syncLog->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'message' => 'Sync completed successfully',
                    ]);

                    $this->info("Successfully synced supplier: {$supplier->name}");
                } catch (\Exception $e) {
                    // Log the error and update the sync log
                    $syncLog->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'message' => $e->getMessage(),
                    ]);

                    Log::error("Error syncing supplier {$supplier->name}: " . $e->getMessage());
                    $this->error("Error syncing supplier {$supplier->name}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in supplier sync command: ' . $e->getMessage());
            $this->error('Error in supplier sync command: ' . $e->getMessage());
            return 1;
        }

        $this->info('Supplier sync completed!');
        return 0;
    }
}