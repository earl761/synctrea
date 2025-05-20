<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_pairs', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('connection_pairs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}; 