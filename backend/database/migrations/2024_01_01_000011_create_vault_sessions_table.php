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
        Schema::create('vault_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id');
            $table->uuid('user_id');
            $table->uuid('device_id')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('max_duration_seconds')->default(600);
            $table->string('status')->comment('active, closed, timeout, alarm');
            $table->boolean('timeout_alarm_triggered')->default(false);
            $table->timestamp('timeout_alarm_at')->nullable();
            $table->string('close_reason')->nullable()->comment('push_button, manual, timeout, emergency');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
            $table->index('vault_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_sessions');
    }
};
