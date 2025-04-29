<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type'); // 'product_sync', 'inventory_sync', 'price_sync', etc.
            $table->string('status'); // 'success', 'failed', 'pending'
            $table->text('message')->nullable();
            $table->json('details')->nullable(); // Store additional sync details
            $table->json('error_data')->nullable(); // Store error information if sync fails
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'type', 'status']);
            $table->index(['destination_id', 'type', 'status']);
            $table->index(['product_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};