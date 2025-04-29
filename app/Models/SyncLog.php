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
    ];

    protected $casts = [
        'details' => 'array',
        'error_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Constants
    public const TYPE_PRODUCT_SYNC = 'product_sync';
    public const TYPE_INVENTORY_SYNC = 'inventory_sync';
    public const TYPE_PRICE_SYNC = 'price_sync';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    // Relationships
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

    // Scopes
    public function scopeRecent($query)
    {
        return $query->orderBy('started_at', 'desc');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public static function createLog(
        string $type,
        Supplier $supplier,
        ?Destination $destination = null,
        ?Product $product = null
    ): self {
        return self::create([
            'supplier_id' => $supplier->id,
            'destination_id' => $destination?->id,
            'product_id' => $product?->id,
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'message' => $message,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $message, array $errorData = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'message' => $message,
            'error_data' => $errorData,
            'completed_at' => now(),
        ]);
    }

    public function getDuration(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}