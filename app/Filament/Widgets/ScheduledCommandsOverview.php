<?php

namespace App\Filament\Widgets;

use App\Services\ScheduledCommandService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ScheduledCommandsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $stats = ScheduledCommandService::getStatistics();
        
        return [
            Stat::make('Total Commands', $stats['total'])
                ->description('Scheduled commands in system')
                ->descriptionIcon('heroicon-m-command-line')
                ->color('primary'),
                
            Stat::make('Active Commands', $stats['active'])
                ->description('Currently enabled')
                ->descriptionIcon('heroicon-m-play')
                ->color('success'),
                
            Stat::make('Due Now', $stats['due_now'])
                ->description('Commands ready to run')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats['due_now'] > 0 ? 'warning' : 'gray'),
                
            Stat::make('Last Status', $this->getLastStatusStat($stats['status_counts']))
                ->description('Recent execution results')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getLastStatusColor($stats['status_counts'])),
        ];
    }
    
    private function getLastStatusStat(array $statusCounts): string
    {
        $completed = $statusCounts['completed'] ?? 0;
        $failed = $statusCounts['failed'] ?? 0;
        $total = $completed + $failed;
        
        if ($total === 0) {
            return 'No runs yet';
        }
        
        $successRate = round(($completed / $total) * 100);
        return "{$successRate}% success";
    }
    
    private function getLastStatusColor(array $statusCounts): string
    {
        $completed = $statusCounts['completed'] ?? 0;
        $failed = $statusCounts['failed'] ?? 0;
        $total = $completed + $failed;
        
        if ($total === 0) {
            return 'gray';
        }
        
        $successRate = ($completed / $total) * 100;
        
        if ($successRate >= 80) {
            return 'success';
        } elseif ($successRate >= 60) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}