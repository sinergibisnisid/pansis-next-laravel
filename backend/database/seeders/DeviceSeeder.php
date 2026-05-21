<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Organization;
use App\Models\Vault;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'BJB')->first();
        $branches = Branch::where('organization_id', $organization->id)->get()->keyBy('code');
        $vaults = Vault::whereIn('branch_id', $branches->pluck('id'))->get();

        $ipCounter = 1;

        foreach ($vaults as $vault) {
            $branch = $branches->firstWhere('id', $vault->branch_id);
            $vaultCode = $vault->code;
            $baseIpPrefix = $this->getIpPrefix($branch->code);

            // Controller
            Device::firstOrCreate(
                ['serial_number' => "CTRL-{$vaultCode}-001"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Controller {$vaultCode}",
                    'serial_number' => "CTRL-{$vaultCode}-001",
                    'type' => DeviceType::Controller,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v2.4.1',
                    'signal_quality' => 95,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subMinutes(1),
                    'is_active' => true,
                    'metadata' => ['model' => 'ESP32-S3', 'manufacturer' => 'Espressif'],
                ]
            );

            // Fingerprint Scanner
            Device::firstOrCreate(
                ['serial_number' => "FPS-{$vaultCode}-001"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Fingerprint Scanner {$vaultCode}",
                    'serial_number' => "FPS-{$vaultCode}-001",
                    'type' => DeviceType::FingerprintScanner,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v1.8.3',
                    'signal_quality' => 92,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subMinutes(2),
                    'is_active' => true,
                    'metadata' => ['model' => 'R503', 'manufacturer' => 'Grow', 'capacity' => 200],
                ]
            );

            // Camera 1
            Device::firstOrCreate(
                ['serial_number' => "CAM-{$vaultCode}-001"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Camera 1 {$vaultCode}",
                    'serial_number' => "CAM-{$vaultCode}-001",
                    'type' => DeviceType::Camera,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v3.1.0',
                    'signal_quality' => 88,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subMinutes(1),
                    'is_active' => true,
                    'metadata' => ['model' => 'DS-2CD2143G2-I', 'manufacturer' => 'Hikvision', 'resolution' => '4MP', 'position' => 'entrance'],
                ]
            );

            // Camera 2
            Device::firstOrCreate(
                ['serial_number' => "CAM-{$vaultCode}-002"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Camera 2 {$vaultCode}",
                    'serial_number' => "CAM-{$vaultCode}-002",
                    'type' => DeviceType::Camera,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v3.1.0',
                    'signal_quality' => 90,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subMinutes(1),
                    'is_active' => true,
                    'metadata' => ['model' => 'DS-2CD2143G2-I', 'manufacturer' => 'Hikvision', 'resolution' => '4MP', 'position' => 'interior'],
                ]
            );

            // Sensor
            Device::firstOrCreate(
                ['serial_number' => "SNS-{$vaultCode}-001"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Door Sensor {$vaultCode}",
                    'serial_number' => "SNS-{$vaultCode}-001",
                    'type' => DeviceType::Sensor,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v1.2.0',
                    'signal_quality' => 97,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subSeconds(30),
                    'is_active' => true,
                    'metadata' => ['model' => 'MC-38', 'manufacturer' => 'Generic', 'sensor_type' => 'magnetic_contact'],
                ]
            );

            // Buzzer
            Device::firstOrCreate(
                ['serial_number' => "BZR-{$vaultCode}-001"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Buzzer {$vaultCode}",
                    'serial_number' => "BZR-{$vaultCode}-001",
                    'type' => DeviceType::Buzzer,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v1.0.5',
                    'signal_quality' => 99,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subMinutes(1),
                    'is_active' => true,
                    'metadata' => ['model' => 'SFM-27', 'manufacturer' => 'Generic', 'decibel' => 120],
                ]
            );

            // Lock
            Device::firstOrCreate(
                ['serial_number' => "LCK-{$vaultCode}-001"],
                [
                    'vault_id' => $vault->id,
                    'branch_id' => $branch->id,
                    'name' => "Electronic Lock {$vaultCode}",
                    'serial_number' => "LCK-{$vaultCode}-001",
                    'type' => DeviceType::Lock,
                    'status' => DeviceStatus::Online,
                    'ip_address' => "{$baseIpPrefix}." . ($ipCounter++),
                    'mac_address' => $this->generateMac(),
                    'firmware_version' => 'v2.0.3',
                    'signal_quality' => 96,
                    'device_token' => Str::random(64),
                    'last_heartbeat_at' => now()->subSeconds(45),
                    'is_active' => true,
                    'metadata' => ['model' => 'EL-500', 'manufacturer' => 'Kaba', 'lock_type' => 'electromagnetic'],
                ]
            );
        }
    }

    private function getIpPrefix(string $branchCode): string
    {
        return match ($branchCode) {
            'KP' => '192.168.10',
            'BDG' => '192.168.20',
            'JKT' => '192.168.30',
            'SBY' => '192.168.40',
            'SMG' => '192.168.50',
            default => '192.168.99',
        };
    }

    private function generateMac(): string
    {
        return implode(':', array_map(function () {
            return strtoupper(str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT));
        }, range(1, 6)));
    }
}
