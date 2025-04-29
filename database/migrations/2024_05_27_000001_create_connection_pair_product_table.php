<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_pair_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_pair_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('catalog_status')->nullable();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['connection_pair_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_pair_product');
    }
};