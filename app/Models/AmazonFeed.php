<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonFeed extends Model
{
    protected $table = 'amazon_feeds';

    protected $fillable = [
        'connection_pair_id',
        'feed_id',
        'feed_type',
        'processing_status',
        'feed_document_id',
        'result_feed_document_id',
        'processing_start_time',
        'processing_end_time',
        'result_summary',
        'errors'
    ];

    protected $casts = [
        'processing_start_time' => 'datetime',
        'processing_end_time' => 'datetime',
        'result_summary' => 'array',
        'errors' => 'array',
    ];

    public function connectionPair()
    {
        return $this->belongsTo(ConnectionPair::class);
    }
}