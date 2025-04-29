<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('customer_number')->nullable()->after('api_endpoint');
            $table->string('sender_id')->nullable()->after('customer_number');
            $table->string('country_code', 2)->nullable()->after('sender_id');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_number',
                'sender_id',
                'country_code',
            ]);
        });
    }
};