<?php

namespace App\Services;

use App\Models\ScheduledCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScheduledCommandService
{
    /**
     * Get all available Artisan commands
     */
    public static function getAvailableCommands(): array
    {
        $commands = [];
        
        // Get all registered Artisan commands
        $artisan = app('Illuminate\Contracts\Console\Kernel');
        $artisanCommands = $artisan->all();
        
        foreach ($artisanCommands as $name => $command) {
            if (!self::shouldSkipCommand($name)) {
                $description = $command->getDescription() ?: 'No description available';
                $commands[$name] = "{$name} - {$description}";
            }
        }
        
        // Add custom commands (only if not already present)
        $customCommands = self::scanCustomCommands();
        foreach ($customCommands as $name => $description) {
            if (!isset($commands[$name])) {
                $commands[$name] = $description;
            }
        }
        
        // Sort commands alphabetically
        asort($commands);
        
        return $commands;
    }
    
    /**
     * Check if a command should be skipped from scheduling
     */
    private static function shouldSkipCommand(string $name): bool
    {
        $skipPatterns = [
            'help',
            'list',
            'tinker',
            'serve',
            'migrate:install',
            'migrate:reset',
            'migrate:refresh',
            'migrate:fresh',
            'db:wipe',
            'schedule:run',
            'schedule:work',
            'horizon:*',
            'telescope:*',
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (Str::is($pattern, $name)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Scan for custom commands in the app directory
     */
    private static function scanCustomCommands(): array
    {
        $commands = [];
        $commandsPath = app_path('Console/Commands');
        
        if (!File::exists($commandsPath)) {
            return $commands;
        }
        
        $files = File::allFiles($commandsPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                
                // Extract command signature using regex
                if (preg_match('/protected \$signature\s*=\s*[\'"]([^\'";]+)[\'"]/', $content, $matches)) {
                    $signature = trim($matches[1]);
                    $commandName = explode(' ', $signature)[0];
                    
                    // Extract description
                    $description = 'Custom command';
                    if (preg_match('/protected \$description\s*=\s*[\'"]([^\'";]+)[\'"]/', $content, $descMatches)) {
                        $description = trim($descMatches[1]);
                    }
                    
                    $commands[$commandName] = "{$commandName} - {$description}";
                }
            }
        }
        
        return $commands;
    }
    
    /**
     * Create a new scheduled command
     */
    public static function createScheduledCommand(array $data): ScheduledCommand
    {
        return ScheduledCommand::create([
            'command' => $data['command'],
            'arguments' => $data['arguments'] ?? [],
            'cron_expression' => $data['cron_expression'],
            'is_enabled' => $data['is_enabled'] ?? true,
            'status' => 'pending',
        ]);
    }
    
    /**
     * Execute a scheduled command immediately
     */
    public static function executeCommand(ScheduledCommand $scheduledCommand, array $overrideArguments = []): array
    {
        try {
            $scheduledCommand->update(['status' => 'running']);
            
            $arguments = array_merge($scheduledCommand->arguments ?? [], $overrideArguments);
            $exitCode = Artisan::call($scheduledCommand->command, $arguments);
            $output = Artisan::output();
            
            $scheduledCommand->update([
                'last_run_at' => now(),
                'last_output' => $output,
                'status' => $exitCode === 0 ? 'completed' : 'failed',
            ]);
            
            return [
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
            ];
            
        } catch (\Exception $e) {
            $scheduledCommand->update([
                'status' => 'failed',
                'last_output' => $e->getMessage(),
                'last_run_at' => now(),
            ]);
            
            return [
                'success' => false,
                'exit_code' => -1,
                'output' => $e->getMessage(),
                'error' => $e,
            ];
        }
    }
    
    /**
     * Get commands that are due to run
     */
    public static function getDueCommands(): \Illuminate\Database\Eloquent\Collection
    {
        return ScheduledCommand::where('is_enabled', true)
            ->get()
            ->filter(function ($command) {
                return $command->shouldRun();
            });
    }
    
    /**
     * Bulk activate/deactivate commands
     */
    public static function bulkToggleCommands(array $commandIds, bool $enabled): int
    {
        return ScheduledCommand::whereIn('id', $commandIds)
            ->update(['is_enabled' => $enabled]);
    }
    
    /**
     * Get command statistics
     */
    public static function getStatistics(): array
    {
        $total = ScheduledCommand::count();
        $active = ScheduledCommand::where('is_enabled', true)->count();
        $inactive = $total - $active;
        
        $statusCounts = ScheduledCommand::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'status_counts' => $statusCounts,
            'due_now' => self::getDueCommands()->count(),
        ];
    }
}