<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->string('upc')->nullable()->after('name');
            $table->string('condition')->default('new')->after('upc');
            $table->string('part_number')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->dropColumn(['upc', 'condition', 'part_number']);
        });
    }
}; 