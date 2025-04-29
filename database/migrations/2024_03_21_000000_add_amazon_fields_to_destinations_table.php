<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('marketplace_id')->nullable();
            $table->string('seller_id')->nullable();
            $table->json('credentials')->nullable();
            $table->boolean('sandbox')->default(false);
        });
    }

    public function down()
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['marketplace_id', 'seller_id', 'credentials', 'sandbox']);
        });
    }
}; 