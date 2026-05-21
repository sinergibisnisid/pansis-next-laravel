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
        Schema::create('device_heartbeats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('status');
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('signal_strength')->nullable();
            $table->integer('uptime_seconds')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('error_count')->default(0);
            $table->string('last_error')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('device_id');
            $table->index('status');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_heartbeats');
    }
};
