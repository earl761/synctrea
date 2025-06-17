<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Cron\CronExpression;
use Carbon\Carbon;

class ScheduledCommand extends Model
{
    protected $fillable = [
        'command',
        'arguments',
        'cron_expression',
        'is_enabled',
        'last_run_at',
        'next_run_at',
        'last_output',
        'status',
    ];

    protected $casts = [
        'arguments' => 'array',
        'is_enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the next run date based on cron expression
     */
    protected function nextRunAt(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_enabled || !$this->cron_expression) {
                    return null;
                }
                
                try {
                    $cron = new CronExpression($this->cron_expression);
                    return Carbon::instance($cron->getNextRunDate());
                } catch (\Exception $e) {
                    return null;
                }
            }
        );
    }

    /**
     * Get the previous run date based on cron expression
     */
    protected function previousRunAt(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->cron_expression) {
                    return null;
                }
                
                try {
                    $cron = new CronExpression($this->cron_expression);
                    return Carbon::instance($cron->getPreviousRunDate());
                } catch (\Exception $e) {
                    return null;
                }
            }
        );
    }

    /**
     * Check if the command should run now
     */
    public function shouldRun(): bool
    {
        if (!$this->is_enabled || !$this->cron_expression) {
            return false;
        }

        try {
            $cron = new CronExpression($this->cron_expression);
            return $cron->isDue();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a human-readable description of the cron schedule
     */
    public function getScheduleDescription(): string
    {
        if (!$this->cron_expression) {
            return 'No schedule set';
        }

        $descriptions = [
            '* * * * *' => 'Every minute',
            '*/5 * * * *' => 'Every 5 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *' => 'Hourly',
            '0 */6 * * *' => 'Every 6 hours',
            '0 */12 * * *' => 'Every 12 hours',
            '0 0 * * *' => 'Daily at midnight',
            '0 9 * * *' => 'Daily at 9 AM',
            '0 0 * * 0' => 'Weekly (Sunday)',
            '0 0 1 * *' => 'Monthly (1st day)',
        ];

        return $descriptions[$this->cron_expression] ?? $this->cron_expression;
    }
}