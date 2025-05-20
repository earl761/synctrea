<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command'); // Artisan command name
            $table->json('arguments')->nullable(); // Arguments/options as JSON
            $table->string('cron_expression'); // Cron schedule
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('last_output')->nullable();
            $table->string('status')->default('idle'); // idle, running, failed, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_commands');
    }
}; 