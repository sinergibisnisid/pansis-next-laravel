<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2-23: Hardware command queue with delivery guarantee.
 *
 * Today HardwareControlService publishes lock/buzzer commands fire-and-forget:
 * if the broker drops it, the command is silently lost. For safety-critical
 * operations (release lock, activate alarm buzzer) we need at-least-once
 * delivery with ack tracking and retry.
 *
 * Flow:
 *   1. Backend creates a row (status=pending) and publishes the MQTT command
 *      with the row id as command_id in the payload.
 *   2. Controller executes the command and publishes ack/{vault_id}/{command_id}
 *      with status=success|error.
 *   3. Backend receives ack → marks row as acknowledged.
 *   4. Worker retries any row stuck in 'sent' beyond its ack_deadline_at,
 *      up to max_attempts times. After that the row becomes 'failed' and
 *      raises a critical alarm.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('hardware_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Target vault & device.
            $table->foreignUuid('vault_id')
                ->constrained('vaults')
                ->cascadeOnDelete();
            $table->foreignUuid('device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();

            // Who issued the command (operator or system).
            $table->foreignUuid('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Command type: lock_release | lock_engage | buzzer_activate | buzzer_deactivate
            $table->string('command_type', 32);

            // The MQTT topic this command is published to.
            $table->string('topic');

            // Full command payload (json).
            $table->jsonb('payload');

            // QoS this command was published with (1 or 2 for safety-critical).
            $table->unsignedTinyInteger('qos')->default(2);

            // Optional reason / context (e.g. "exit_button_pressed", "occupancy_timeout").
            $table->string('reason', 64)->nullable();

            // Lifecycle:
            //   pending     — created, not yet published (DB transaction in progress)
            //   sent        — published to broker, waiting for ack
            //   acknowledged — controller confirmed execution
            //   failed      — exceeded max_attempts without ack
            //   cancelled   — explicitly cancelled by operator/system
            $table->string('status', 16)->default('pending');

            // Number of publish attempts so far.
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            // Most recent timestamps.
            $table->timestamp('first_sent_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();

            // Deadline by which we expect an ack. Worker re-sends if past this time.
            $table->timestamp('ack_deadline_at')->nullable();

            // Ack details.
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('ack_status', 16)->nullable()
                ->comment('success | error | timeout');
            $table->text('ack_error')->nullable();

            // Final failure timestamp.
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'ack_deadline_at']);
            $table->index('vault_id');
            $table->index('command_type');
            $table->index('issued_by');
            $table->index('first_sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_commands');
    }
};
