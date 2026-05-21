<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VaultSession>
 */
class VaultSessionFactory extends Factory
{
    protected $model = VaultSession::class;

    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'user_id' => User::factory(),
            'opened_at' => now(),
            'closed_at' => null,
            'duration_seconds' => null,
            'max_duration_seconds' => 600,
            'status' => SessionStatus::Active,
            'timeout_alarm_triggered' => false,
            'timeout_alarm_at' => null,
            'close_reason' => null,
            'metadata' => null,
        ];
    }

    public function closed(): static
    {
        $openedAt = now()->subMinutes(fake()->numberBetween(1, 10));
        $closedAt = now();
        $duration = $closedAt->diffInSeconds($openedAt);

        return $this->state(fn (array $attributes) => [
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'duration_seconds' => $duration,
            'status' => SessionStatus::Closed,
            'close_reason' => 'normal',
        ]);
    }

    public function timedOut(): static
    {
        $openedAt = now()->subMinutes(15);
        $closedAt = now();

        return $this->state(fn (array $attributes) => [
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'duration_seconds' => 900,
            'max_duration_seconds' => 600,
            'status' => SessionStatus::Timeout,
            'timeout_alarm_triggered' => true,
            'timeout_alarm_at' => $openedAt->addSeconds(600),
            'close_reason' => 'timeout',
        ]);
    }

    public function forVault(Vault $vault): static
    {
        return $this->state(fn (array $attributes) => [
            'vault_id' => $vault->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
