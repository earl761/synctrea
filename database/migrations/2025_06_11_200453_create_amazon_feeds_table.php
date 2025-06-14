<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('amazon_feeds')) {
            Schema::create('amazon_feeds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('connection_pair_id');
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
                
                $table->foreign('connection_pair_id')->references('id')->on('connection_pairs')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('amazon_feeds');
    }
};