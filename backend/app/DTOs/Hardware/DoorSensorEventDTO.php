<?php

namespace App\DTOs\Hardware;

/**
 * Door sensor event payload.
 * MQTT topic: door/{vault_id}/opened or door/{vault_id}/closed
 *
 * Payload example:
 *   { "vault_id": "uuid", "device_id": "uuid", "state": "opened",
 *     "occurred_at": "2026-05-27T10:00:00Z" }
 */
readonly class DoorSensorEventDTO
{
    public function __construct(
        public string $vaultId,
        public ?string $deviceId,
        public string $state,           // 'opened' | 'closed'
        public ?\DateTimeImmutable $occurredAt = null,
        public ?array $metadata = null,
    ) {}

    public static function fromPayload(array $payload): self
    {
        $occurredAt = isset($payload['occurred_at'])
            ? new \DateTimeImmutable($payload['occurred_at'])
            : null;

        return new self(
            vaultId: $payload['vault_id'],
            deviceId: $payload['device_id'] ?? null,
            state: $payload['state'] ?? 'unknown',
            occurredAt: $occurredAt,
            metadata: $payload['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'device_id' => $this->deviceId,
            'state' => $this->state,
            'occurred_at' => $this->occurredAt?->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }
}
