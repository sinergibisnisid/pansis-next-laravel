<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-9: Persist the I/O channel topology of an Intelligence Controller device.
 *
 * Per Pansin Access PDF "Intelligence Controller":
 *   - 4 sensor input channels (S1–S4): door sensor, exit button, emergency button, etc.
 *   - 4 relay output channels (R1–R4): magnetic lock, buzzer, optional auxiliary outputs.
 *
 * Each channel is mapped to a logical function (door_sensor, exit_button,
 * emergency_button, magnetic_lock, buzzer, …) so the backend knows which
 * physical channel to read / drive when issuing commands.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('controller_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Owning device (must be a Controller-type device).
            $table->foreignUuid('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            // Optional: which vault this channel belongs to (if controller serves >1 vault).
            $table->foreignUuid('vault_id')
                ->nullable()
                ->constrained('vaults')
                ->nullOnDelete();

            // Direction of the channel: 'input' (sensor) or 'output' (relay).
            $table->string('direction', 16)
                ->comment('input | output');

            // Channel index on the controller (1..n). Combined with direction must be unique
            // per device so the same controller cannot have two "S1" inputs.
            $table->unsignedTinyInteger('channel_number');

            // Logical function this channel performs.
            $table->string('function', 32)
                ->comment('door_sensor | exit_button | emergency_button | magnetic_lock | buzzer | aux_input | aux_output');

            // Optional human-readable label, e.g. "Door Sensor — Vault Utama".
            $table->string('label')->nullable();

            // Electrical configuration:
            // - For inputs: 'normally_open' or 'normally_closed' (NC = emergency button per PDF).
            // - For outputs: usually 'normally_open' (relay closes circuit when energized).
            $table->string('normal_state', 32)->default('normally_open')
                ->comment('normally_open | normally_closed');

            // Whether this channel is currently wired and active.
            $table->boolean('is_active')->default(true);

            // Whether the channel mapping is auto-discovered or manually configured.
            $table->boolean('is_auto_discovered')->default(false);

            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('device_id');
            $table->index('vault_id');
            $table->index('function');
            $table->index(['device_id', 'direction']);
            // Channel number is unique per device + direction (one S1, one R1 each).
            $table->unique(['device_id', 'direction', 'channel_number'], 'controller_channels_unique_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controller_channels');
    }
};
