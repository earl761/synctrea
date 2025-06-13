<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // SFTP credentials will be stored in the existing encrypted credentials JSON column
            // No new columns needed as we'll use:
            // - credentials->sftp_host
            // - credentials->sftp_username
            // - credentials->sftp_password
            // - credentials->sftp_path
        });
    }

    public function down(): void
    {
        // No columns to drop as we're using the existing credentials column
    }
};