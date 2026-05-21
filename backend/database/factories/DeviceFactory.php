<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'branch_id' => Branch::factory(),
            'name' => fake()->word() . ' Device',
            'serial_number' => fake()->unique()->lexify('DEV-########'),
            'type' => fake()->randomElement(DeviceType::values()),
            'status' => DeviceStatus::Online,
            'ip_address' => fake()->localIpv4(),
            'mac_address' => fake()->macAddress(),
            'firmware_version' => fake()->semver(),
            'signal_quality' => fake()->numberBetween(60, 100),
            'device_token' => generate_device_token(),
            'last_heartbeat_at' => now(),
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Offline,
            'last_heartbeat_at' => now()->subMinutes(30),
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Maintenance,
        ]);
    }

    public function forVault(Vault $vault): static
    {
        return $this->state(fn (array $attributes) => [
            'vault_id' => $vault->id,
            'branch_id' => $vault->branch_id,
        ]);
    }

    public function ofType(DeviceType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
