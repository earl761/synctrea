<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->string('price_override_type')->default('none')->after('catalog_status');
        });
    }

    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->dropColumn('price_override_type');
        });
    }
}; 