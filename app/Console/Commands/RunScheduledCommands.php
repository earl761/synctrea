<?php

namespace App\Console\Commands;

use App\Models\ScheduledCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunScheduledCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:run-commands {--force : Force run all enabled commands regardless of schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled commands that are due to execute';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $query = ScheduledCommand::where('is_enabled', true);
        
        if (!$force) {
            // Only get commands that should run now
            $commands = $query->get()->filter(function ($command) {
                return $command->shouldRun();
            });
        } else {
            $commands = $query->get();
        }

        if ($commands->isEmpty()) {
            $this->info('No commands are scheduled to run at this time.');
            return 0;
        }

        $this->info("Found {$commands->count()} command(s) to execute.");

        foreach ($commandsToRun as $scheduledCommand) {
            $this->executeScheduledCommand($scheduledCommand);
        }

        $this->info('All scheduled commands have been processed.');
        return 0;
    }

    /**
     * Execute a single scheduled command
     */
    protected function executeScheduledCommand(ScheduledCommand $scheduledCommand): void
    {
        $this->line("Running: {$scheduledCommand->command}");
        
        try {
            // Update status to running
            $scheduledCommand->update(['status' => 'running']);
            
            // Execute the command
            $arguments = $scheduledCommand->arguments ?? [];
            $exitCode = Artisan::call($scheduledCommand->command, $arguments);
            $output = Artisan::output();
            
            // Update the record with results
            $scheduledCommand->update([
                'last_run_at' => now(),
                'last_output' => $output,
                'status' => $exitCode === 0 ? 'completed' : 'failed',
            ]);
            
            if ($exitCode === 0) {
                $this->info("✓ {$scheduledCommand->command} completed successfully");
            } else {
                $this->error("✗ {$scheduledCommand->command} failed with exit code: {$exitCode}");
            }
            
            // Log the execution
            Log::info('Scheduled command executed', [
                'command' => $scheduledCommand->command,
                'arguments' => $arguments,
                'exit_code' => $exitCode,
                'output_length' => strlen($output),
            ]);
            
        } catch (\Exception $e) {
            // Handle execution errors
            $scheduledCommand->update([
                'status' => 'failed',
                'last_output' => $e->getMessage(),
                'last_run_at' => now(),
            ]);
            
            $this->error("✗ {$scheduledCommand->command} failed: {$e->getMessage()}");
            
            Log::error('Scheduled command failed', [
                'command' => $scheduledCommand->command,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}