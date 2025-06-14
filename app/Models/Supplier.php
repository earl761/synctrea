<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Api\IngramMicroApiClient;
use App\Services\Api\DHApiClient;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'api_key',
        'api_secret',
        'api_endpoint',
        'customer_number',
        'sender_id',
        'country_code',
        'credentials',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'credentials' => 'json',
        'settings' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    // Constants for supplier types
    public const TYPE_INGRAM_MICRO = 'ingram_micro';
    public const TYPE_DH = 'dh';

    public function destinations()
    {
        return $this->belongsToMany(Destination::class, 'connection_pairs')
            ->withPivot('settings', 'is_active')
            ->withTimestamps()
            ->using(ConnectionPair::class);
    }

    public function connectionPairs(): HasMany
    {
        return $this->hasMany(ConnectionPair::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    // Helper methods for API integration
    public function getApiClient()
    {
        // Factory method to create appropriate API client based on supplier type
        return match($this->type) {
            self::TYPE_INGRAM_MICRO => new IngramMicroApiClient($this),
            self::TYPE_DH => new DHApiClient($this),
            default => throw new \Exception('Unsupported supplier type'),
        };
    }

    public function isIngramMicro(): bool
    {
        return $this->type === self::TYPE_INGRAM_MICRO;
    }

    // SFTP-related methods
    public function getSftpConnection(): Filesystem
    {
        if (!$this->credentials || empty($this->credentials['sftp_host'])) {
            throw new \Exception('SFTP credentials not configured');
        }

        return new Filesystem(new SftpAdapter(
            new SftpConnectionProvider(
                $this->credentials['sftp_host'],
                $this->credentials['sftp_username'],
                $this->credentials['sftp_password']
            )
        ));
    }

    public function hasSftpCredentials(): bool
    {
        return $this->credentials && 
            !empty($this->credentials['sftp_host']) && 
            !empty($this->credentials['sftp_username']) && 
            !empty($this->credentials['sftp_password']);
    }

    public function getSftpPath(): string
    {
        return $this->credentials['sftp_path'] ?? '/PRICE.ZIP';
    }
}