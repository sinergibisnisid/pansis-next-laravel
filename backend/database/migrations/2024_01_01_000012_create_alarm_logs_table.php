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
        Schema::create('alarm_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id')->nullable();
            $table->uuid('device_id')->nullable();
            $table->uuid('branch_id');
            $table->uuid('user_id')->nullable();
            $table->string('alarm_type')->comment('unauthorized_access, session_timeout, device_tamper, emergency, sensor_trigger, manual');
            $table->string('severity')->comment('low, medium, high, critical');
            $table->string('status')->comment('active, acknowledged, resolved, false_alarm');
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('triggered_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('set null');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('vault_id');
            $table->index('branch_id');
            $table->index('alarm_type');
            $table->index('severity');
            $table->index('status');
            $table->index('triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_logs');
    }
};
