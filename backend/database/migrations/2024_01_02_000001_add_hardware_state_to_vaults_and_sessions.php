<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 enhancement: Track physical hardware state separately from logical vault status.
 *
 * Per Pansin Access PDF "Vault Access Workflow":
 *  - Timer starts when DOOR SENSOR detects door is opened (not when fingerprint approved).
 *  - Buzzer is a physical relay on the controller, separate from logical alarm state.
 *  - Magnetic lock relay state must be observable for audit / safety.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('vaults', function (Blueprint $table) {
            // Physical door sensor state — driven by door/+/opened, door/+/closed MQTT events.
            $table->string('door_state')->default('closed')->after('status')
                ->comment('closed, opened, unknown — driven by door sensor');

            // Magnetic lock relay state — driven by lock/+/release, lock/+/engage commands & ack.
            $table->string('lock_state')->default('engaged')->after('door_state')
                ->comment('engaged, released, unknown — magnetic lock relay');

            // Buzzer relay state — driven by buzzer/+/activate, buzzer/+/deactivate.
            $table->string('buzzer_state')->default('off')->after('lock_state')
                ->comment('on, off — physical alarm buzzer relay');

            // Last time door state changed (used to compute occupancy duration).
            $table->timestamp('door_state_changed_at')->nullable()->after('buzzer_state');

            $table->index('door_state');
            $table->index('lock_state');
            $table->index('buzzer_state');
        });

        Schema::table('vault_sessions', function (Blueprint $table) {
            // Timestamp when door physically opened (per door sensor).
            // opened_at = fingerprint approved, door_opened_at = door actually opened.
            $table->timestamp('door_opened_at')->nullable()->after('opened_at');

            // Timestamp when door physically closed.
            $table->timestamp('door_closed_at')->nullable()->after('door_opened_at');

            // Timestamp when exit push button pressed (intent to exit).
            $table->timestamp('exit_button_pressed_at')->nullable()->after('door_closed_at');

            // Timestamp when emergency button pressed during this session.
            $table->timestamp('emergency_button_pressed_at')->nullable()->after('exit_button_pressed_at');

            $table->index('door_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('vault_sessions', function (Blueprint $table) {
            $table->dropIndex(['door_opened_at']);
            $table->dropColumn([
                'door_opened_at',
                'door_closed_at',
                'exit_button_pressed_at',
                'emergency_button_pressed_at',
            ]);
        });

        Schema::table('vaults', function (Blueprint $table) {
            $table->dropIndex(['door_state']);
            $table->dropIndex(['lock_state']);
            $table->dropIndex(['buzzer_state']);
            $table->dropColumn([
                'door_state',
                'lock_state',
                'buzzer_state',
                'door_state_changed_at',
            ]);
        });
    }
};
