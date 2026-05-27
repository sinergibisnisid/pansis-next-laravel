<?php

namespace App\DTOs\Hardware;

use App\Enums\ButtonType;

/**
 * Physical button press event from controller.
 * MQTT topic:
 *   button/{vault_id}/exit_pressed     — Exit Push Button (Normally Open)
 *   button/{vault_id}/emergency        — Emergency Button (Normally Closed)
 */
readonly class ButtonEventDTO
{
    public function __construct(
        public string $vaultId,
        public ?string $deviceId,
        public ButtonType $buttonType,
        public ?\DateTimeImmutable $occurredAt = null,
        public ?array $metadata = null,
    ) {}

    public static function fromPayload(array $payload, ButtonType $buttonType): self
    {
        $occurredAt = isset($payload['occurred_at'])
            ? new \DateTimeImmutable($payload['occurred_at'])
            : null;

        return new self(
            vaultId: $payload['vault_id'],
            deviceId: $payload['device_id'] ?? null,
            buttonType: $buttonType,
            occurredAt: $occurredAt,
            metadata: $payload['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'device_id' => $this->deviceId,
            'button_type' => $this->buttonType->value,
            'occurred_at' => $this->occurredAt?->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }
}
