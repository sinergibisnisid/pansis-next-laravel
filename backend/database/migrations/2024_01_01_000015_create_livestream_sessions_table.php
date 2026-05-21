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
        Schema::create('livestream_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignUuid('vault_id')->nullable()->constrained('vaults')->nullOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('stream_path');
            $table->string('stream_url')->nullable();
            $table->string('webrtc_url')->nullable();
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('stream_token')->nullable();
            $table->string('quality')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('vault_id');
            $table->index('branch_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livestream_sessions');
    }
};
