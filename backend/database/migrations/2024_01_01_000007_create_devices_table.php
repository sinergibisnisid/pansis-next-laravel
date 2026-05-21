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
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id')->nullable();
            $table->uuid('branch_id');
            $table->string('name');
            $table->string('serial_number')->unique();
            $table->string('type')->comment('controller, fingerprint_scanner, camera, sensor, buzzer, lock');
            $table->string('status')->comment('online, offline, maintenance, error');
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('firmware_version')->nullable();
            $table->integer('signal_quality')->nullable();
            $table->string('device_token')->nullable()->unique();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index('vault_id');
            $table->index('branch_id');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
