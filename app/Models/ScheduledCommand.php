<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
} 