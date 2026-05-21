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
        Schema::create('vault_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('device_id')->nullable();
            $table->string('access_type')->comment('fingerprint, manual_override, emergency, maintenance');
            $table->string('status')->comment('granted, denied, alarm_triggered');
            $table->string('denial_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('accessed_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
            $table->index('vault_id');
            $table->index('user_id');
            $table->index('access_type');
            $table->index('status');
            $table->index('accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_access_logs');
    }
};
