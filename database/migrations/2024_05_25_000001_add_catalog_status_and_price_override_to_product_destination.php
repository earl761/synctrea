<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_destination', function (Blueprint $table) {
            $table->string('catalog_status')->default('default')->after('sync_status'); // default, queued, in_catalog
            $table->decimal('override_price', 10, 2)->nullable()->after('sale_price');
            $table->decimal('markup_percentage', 8, 4)->nullable()->after('override_price');
            $table->boolean('use_override_price')->default(false)->after('markup_percentage');
            $table->index('catalog_status');
        });
    }

    public function down(): void
    {
        Schema::table('product_destination', function (Blueprint $table) {
            $table->dropColumn([
                'catalog_status',
                'override_price',
                'markup_percentage',
                'use_override_price'
            ]);
        });
    }
};