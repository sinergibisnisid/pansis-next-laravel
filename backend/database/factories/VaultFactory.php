<?php

namespace Database\Factories;

use App\Enums\VaultStatus;
use App\Enums\VaultType;
use App\Models\Branch;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vault>
 */
class VaultFactory extends Factory
{
    protected $model = Vault::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Vault ' . fake()->word(),
            'code' => fake()->unique()->lexify('???-V###'),
            'type' => fake()->randomElement(VaultType::values()),
            'status' => VaultStatus::Locked,
            'floor' => fake()->randomElement(['B1', 'GF', '1', '2']),
            'room' => fake()->randomElement(['Room A', 'Room B', 'Room C']),
            'max_session_duration_minutes' => 10,
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

    public function unlocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VaultStatus::Unlocked,
        ]);
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch->id,
        ]);
    }

    public function ofType(VaultType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
