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
        Schema::create('fingerprint_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_id');
            $table->uuid('user_id');
            $table->string('fingerprint_id');
            $table->string('finger_position')->comment('left_thumb, left_index, left_middle, left_ring, left_pinky, right_thumb, right_index, right_middle, right_ring, right_pinky');
            $table->text('template_data')->nullable();
            $table->integer('quality_score')->nullable();
            $table->timestamp('registered_at');
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('device_id');
            $table->index('user_id');
            $table->index('fingerprint_id');
            $table->unique(['device_id', 'fingerprint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerprint_devices');
    }
};
