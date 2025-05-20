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
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_users')->default(1);
            $table->integer('max_connections')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add subscription_package_id to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('subscription_package_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove subscription_package_id from companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['subscription_package_id']);
            $table->dropColumn('subscription_package_id');
        });

        Schema::dropIfExists('subscription_packages');
    }
};
