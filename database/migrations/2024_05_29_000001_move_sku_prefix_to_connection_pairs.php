<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add sku_prefix column to connection_pairs table
        Schema::table('connection_pairs', function (Blueprint $table) {
            $table->string('sku_prefix')->nullable()->after('destination_id');
        });

        // Copy existing sku_prefix values from destinations to connection_pairs
        $destinations = DB::table('destinations')->whereNotNull('sku_prefix')->get();
        foreach ($destinations as $destination) {
            DB::table('connection_pairs')
                ->where('destination_id', $destination->id)
                ->update(['sku_prefix' => $destination->sku_prefix]);
        }

        // Remove sku_prefix column from destinations table
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn('sku_prefix');
        });
    }

    public function down(): void
    {
        // Add sku_prefix column back to destinations table
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('sku_prefix')->nullable()->after('name');
        });

        // Copy sku_prefix values back from connection_pairs to destinations
        $connectionPairs = DB::table('connection_pairs')->whereNotNull('sku_prefix')->get();
        foreach ($connectionPairs as $pair) {
            DB::table('destinations')
                ->where('id', $pair->destination_id)
                ->update(['sku_prefix' => $pair->sku_prefix]);
        }

        // Remove sku_prefix column from connection_pairs table
        Schema::table('connection_pairs', function (Blueprint $table) {
            $table->dropColumn('sku_prefix');
        });
    }
};