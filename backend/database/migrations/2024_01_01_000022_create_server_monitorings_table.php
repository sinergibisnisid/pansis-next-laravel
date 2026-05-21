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
        Schema::create('server_monitorings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hostname');
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->integer('memory_total_mb')->nullable();
            $table->integer('memory_used_mb')->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable();
            $table->integer('disk_total_gb')->nullable();
            $table->integer('disk_used_gb')->nullable();
            $table->integer('queue_size')->nullable();
            $table->integer('queue_failed')->nullable();
            $table->integer('websocket_connections')->nullable();
            $table->boolean('mqtt_connected')->nullable();
            $table->integer('mqtt_messages_in')->nullable();
            $table->integer('mqtt_messages_out')->nullable();
            $table->integer('active_streams')->nullable();
            $table->integer('uptime_seconds')->nullable();
            $table->jsonb('load_average')->nullable();
            $table->timestamp('recorded_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('hostname');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_monitorings');
    }
};
