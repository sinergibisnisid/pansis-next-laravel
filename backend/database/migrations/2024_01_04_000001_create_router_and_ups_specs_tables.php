<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2-17 / P2-18: Router and UPS specific metadata.
 *
 * Per Pansin Access PDF:
 *   Router PoE — VPN client, firewall, DHCP, PoE output, optional internet failover.
 *   UPS        — backup minimum 2 jam, controller stays alive on power loss.
 *
 * We don't need a whole new table per device type. Devices already exist;
 * we add light-weight specialized metadata tables linked one-to-one.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('router_specs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->unique()
                ->constrained('devices')
                ->cascadeOnDelete();

            // Network configuration
            $table->string('lan_ip')->nullable();
            $table->string('wan_ip_primary')->nullable();
            $table->string('wan_ip_secondary')->nullable();
            $table->string('isp_primary')->nullable();
            $table->string('isp_secondary')->nullable();

            // Connectivity capabilities
            $table->boolean('vpn_enabled')->default(false);
            $table->string('vpn_type', 16)->nullable()
                ->comment('wireguard | openvpn | ipsec');
            $table->string('vpn_endpoint')->nullable();
            $table->boolean('failover_enabled')->default(false);
            $table->boolean('poe_enabled')->default(false);
            $table->unsignedSmallInteger('poe_ports')->nullable();
            $table->unsignedSmallInteger('lan_ports')->nullable();

            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ups_specs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->unique()
                ->constrained('devices')
                ->cascadeOnDelete();

            // Optional 1:1 with the device the UPS is powering. UPS is usually
            // wired into the controller but may power router + camera too.
            $table->foreignUuid('powers_device_id')->nullable()
                ->constrained('devices')
                ->nullOnDelete();

            // UPS hardware spec
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('capacity_va')->nullable()
                ->comment('VA rating');
            $table->unsignedSmallInteger('capacity_w')->nullable()
                ->comment('Watt rating');
            $table->unsignedSmallInteger('battery_runtime_minutes')->nullable()
                ->comment('Manufacturer-rated runtime under typical load');
            $table->date('battery_installed_at')->nullable();
            $table->date('battery_replace_due_at')->nullable();

            // Latest snapshot from heartbeat (denormalized for quick reads).
            $table->boolean('on_battery')->nullable();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->unsignedSmallInteger('runtime_remaining_minutes')->nullable();
            $table->timestamp('last_status_at')->nullable();

            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ups_specs');
        Schema::dropIfExists('router_specs');
    }
};
