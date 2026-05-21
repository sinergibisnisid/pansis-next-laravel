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
        Schema::create('fingerprint_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fingerprint_device_id')->nullable();
            $table->uuid('device_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('vault_id')->nullable();
            $table->string('scan_result')->comment('success, failed, rejected, timeout');
            $table->integer('confidence_score')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('scanned_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('fingerprint_device_id')->references('id')->on('fingerprint_devices')->onDelete('set null');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('set null');
            $table->index('device_id');
            $table->index('user_id');
            $table->index('vault_id');
            $table->index('scan_result');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerprint_logs');
    }
};
