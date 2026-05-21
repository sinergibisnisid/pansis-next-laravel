<?php

namespace Database\Factories;

use App\Enums\AlarmStatus;
use App\Enums\AlarmType;
use App\Enums\Severity;
use App\Models\AlarmLog;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlarmLog>
 */
class AlarmLogFactory extends Factory
{
    protected $model = AlarmLog::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'vault_id' => null,
            'device_id' => null,
            'user_id' => null,
            'alarm_type' => fake()->randomElement(AlarmType::values()),
            'severity' => fake()->randomElement(Severity::values()),
            'status' => AlarmStatus::Active,
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
            'triggered_at' => now(),
            'metadata' => null,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AlarmStatus::Acknowledged,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AlarmStatus::Resolved,
            'acknowledged_at' => now()->subMinutes(10),
            'resolved_at' => now(),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => Severity::Critical,
        ]);
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch->id,
        ]);
    }

    public function ofType(AlarmType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'alarm_type' => $type,
        ]);
    }
}
