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
        Schema::create('mqtt_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('topic');
            $table->jsonb('payload');
            $table->string('direction');
            $table->foreignUuid('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->integer('qos')->default(0);
            $table->boolean('retained')->default(false);
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('topic');
            $table->index('device_id');
            $table->index('direction');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mqtt_logs');
    }
};
