<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-10: Add network + power fields to device_heartbeats so HQ can monitor
 * branch infrastructure health without relying on a separate Router/UPS table.
 *
 * Per Pansin Access PDF "Fail-Safe & Redundancy":
 *   - HTTPS + VPN must be tracked (vpn_connected).
 *   - UPS Backup minimum 2 jam (ups_*).
 *   - Network Failover (wan_status, isp_provider, primary/failover).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('device_heartbeats', function (Blueprint $table) {
            // WAN / Internet status reported by router/controller.
            $table->string('wan_status', 16)->nullable()->after('ip_address')
                ->comment('online | offline | degraded | failover');

            // ISP / link identifier so we can tell which uplink is in use during failover.
            $table->string('isp_provider')->nullable()->after('wan_status');

            // Whether VPN tunnel back to HQ is currently established.
            $table->boolean('vpn_connected')->nullable()->after('isp_provider');

            // VPN remote endpoint (e.g. wireguard public key, openvpn server).
            $table->string('vpn_endpoint')->nullable()->after('vpn_connected');

            // Whether the device is currently running on UPS battery (mains failed).
            $table->boolean('ups_on_battery')->nullable()->after('vpn_endpoint');

            // UPS battery percentage (0-100). Nullable when device has no UPS.
            $table->unsignedTinyInteger('ups_battery_percent')->nullable()->after('ups_on_battery');

            // Estimated minutes of UPS runtime remaining.
            $table->unsignedSmallInteger('ups_runtime_minutes')->nullable()->after('ups_battery_percent');

            $table->index('wan_status');
            $table->index('vpn_connected');
            $table->index('ups_on_battery');
        });
    }

    public function down(): void
    {
        Schema::table('device_heartbeats', function (Blueprint $table) {
            $table->dropIndex(['wan_status']);
            $table->dropIndex(['vpn_connected']);
            $table->dropIndex(['ups_on_battery']);
            $table->dropColumn([
                'wan_status',
                'isp_provider',
                'vpn_connected',
                'vpn_endpoint',
                'ups_on_battery',
                'ups_battery_percent',
                'ups_runtime_minutes',
            ]);
        });
    }
};
