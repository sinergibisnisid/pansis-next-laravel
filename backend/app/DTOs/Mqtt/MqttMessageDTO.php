<?php

namespace App\DTOs\Mqtt;

use Illuminate\Http\Request;

readonly class MqttMessageDTO
{
    public function __construct(
        public string $topic,
        public string|array $payload,
        public int $qos = 0,
        public bool $retained = false,
        public ?string $deviceId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            topic: $request->input('topic'),
            payload: $request->input('payload'),
            qos: $request->input('qos', 0),
            retained: $request->input('retained', false),
            deviceId: $request->input('device_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'topic' => $this->topic,
            'payload' => $this->payload,
            'qos' => $this->qos,
            'retained' => $this->retained,
            'device_id' => $this->deviceId,
        ];
    }
}
