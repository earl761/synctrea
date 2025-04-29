<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            // Add new rule types for combined pricing
            $table->decimal('percentage_value', 8, 2)->nullable()->after('value');
            $table->decimal('amount_value', 10, 2)->nullable()->after('percentage_value');
            $table->string('calculation_order')->nullable()->after('amount_value'); // 'percentage_first' or 'amount_first'
        });

        Schema::table('destinations', function (Blueprint $table) {
            $table->string('sku_prefix')->nullable()->after('name');
        });

        // Update existing rule_type enum values
        DB::statement("ALTER TABLE pricing_rules MODIFY COLUMN rule_type ENUM('percentage_markup', 'flat_markup', 'tiered', 'percentage_amount', 'amount_percentage')");
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['percentage_value', 'amount_value', 'calculation_order']);
        });

        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn('sku_prefix');
        });

        // Revert rule_type enum values
        DB::statement("ALTER TABLE pricing_rules MODIFY COLUMN rule_type ENUM('percentage_markup', 'flat_markup', 'tiered')");
    }
};