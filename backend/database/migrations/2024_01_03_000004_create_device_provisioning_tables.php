<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-12: Device provisioning flow.
 *
 * Two-step provisioning:
 *   1. Admin generates a one-time claim code via dashboard. The code is bound
 *      to a target branch and (optionally) vault, with an expires_at timestamp.
 *   2. Device boots, calls /api/v1/devices/provision/claim with the claim code
 *      + its serial number + MAC. Backend verifies the code is unused and not
 *      expired, then issues:
 *        - a per-device API token (sha256-hashed in devices.device_token)
 *        - per-device MQTT credentials (mqtt_username + mqtt_password_hash)
 *        - an ACL (which topics this device can publish/subscribe to)
 *
 * Two new tables:
 *   - device_claim_codes: short-lived one-time codes used during provisioning.
 *   - device_mqtt_credentials: persistent MQTT username/password + ACL per device.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_claim_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Branch the claim is bound to (device will be created in this branch).
            $table->foreignUuid('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            // Vault the device is being provisioned for (nullable: branch-level device).
            $table->foreignUuid('vault_id')
                ->nullable()
                ->constrained('vaults')
                ->nullOnDelete();

            // Admin who created the code.
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Human-readable short code (e.g. 8 chars, base32). Stored hashed for
            // security, not in plaintext.
            $table->string('code_hash')->unique();

            // Last 4 chars of the plaintext code, kept so admins can identify
            // which code is which without storing the full plaintext.
            $table->string('code_suffix', 8);

            // Device metadata expectations: type, name. Used to pre-fill the
            // Device record on claim.
            $table->string('expected_device_type', 32)
                ->comment('controller | fingerprint_scanner | camera | sensor | buzzer | lock');
            $table->string('expected_device_name')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            // After successful claim, points to the created device.
            $table->foreignUuid('used_by_device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();

            // Optional notes from admin.
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('branch_id');
            $table->index('vault_id');
            $table->index('expires_at');
            $table->index('used_at');
        });

        Schema::create('device_mqtt_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            // MQTT username (typically equals device serial number).
            $table->string('mqtt_username')->unique();

            // bcrypt hash of the MQTT password.
            $table->string('mqtt_password_hash');

            // Per-device ACL: topics this device may publish to.
            $table->jsonb('publish_acl');

            // Per-device ACL: topics this device may subscribe to.
            $table->jsonb('subscribe_acl');

            // Whether this credential is currently active. Allows rotation/revocation
            // without deleting history.
            $table->boolean('is_active')->default(true);

            // Optional expiration for short-lived credentials.
            $table->timestamp('expires_at')->nullable();

            // When the credential was last used (last seen on broker).
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('device_id');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_mqtt_credentials');
        Schema::dropIfExists('device_claim_codes');
    }
};
