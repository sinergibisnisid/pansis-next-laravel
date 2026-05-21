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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('maintenance_plan_id')->nullable()->constrained('maintenance_plans')->nullOnDelete();
            $table->foreignUuid('vault_id')->nullable()->constrained('vaults')->nullOnDelete();
            $table->foreignUuid('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('performed_by')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('findings')->nullable();
            $table->text('actions_taken')->nullable();
            $table->jsonb('parts_replaced')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->jsonb('attachments')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('maintenance_plan_id');
            $table->index('vault_id');
            $table->index('device_id');
            $table->index('branch_id');
            $table->index('performed_by');
            $table->index('type');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
