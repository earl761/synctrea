<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('amazon_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_pair_id')->constrained()->cascadeOnDelete();
            $table->string('feed_id');
            $table->string('feed_type');
            $table->string('processing_status');
            $table->string('feed_document_id')->nullable();
            $table->string('result_feed_document_id')->nullable();
            $table->timestamp('processing_start_time')->nullable();
            $table->timestamp('processing_end_time')->nullable();
            $table->json('result_summary')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['connection_pair_id', 'feed_id']);
            $table->index('processing_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('amazon_feeds');
    }
};