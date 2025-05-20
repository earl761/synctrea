<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'destination_id',
        'product_id',
        'type',
        'status',
        'message',
        'details',
        'error_data',
        'started_at',
        'completed_at',
        'error'
    ];

    protected $casts = [
        'details' => 'json',
        'error_data' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function connectionPair(): BelongsTo
    {
        return $this->belongsTo(ConnectionPair::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}