<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->string('price_rule_type')->nullable()->after('price_override');
            $table->decimal('price_rule_value', 10, 2)->nullable()->after('price_rule_type');
            $table->string('price_rule_secondary_type')->nullable()->after('price_rule_value');
            $table->decimal('price_rule_secondary_value', 10, 2)->nullable()->after('price_rule_secondary_type');
        });
    }

    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->dropColumn([
                'price_rule_type',
                'price_rule_value',
                'price_rule_secondary_type',
                'price_rule_secondary_value'
            ]);
        });
    }
}; 