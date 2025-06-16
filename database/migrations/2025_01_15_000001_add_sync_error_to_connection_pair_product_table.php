<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            if (!Schema::hasColumn('connection_pair_product', 'sync_error')) {
                $table->text('sync_error')->nullable()->after('last_sync_attempt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            if (Schema::hasColumn('connection_pair_product', 'sync_error')) {
                $table->dropColumn('sync_error');
            }
        });
    }
};