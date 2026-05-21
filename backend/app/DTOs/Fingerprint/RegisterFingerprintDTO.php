<?php

namespace App\DTOs\Fingerprint;

use Illuminate\Http\Request;

readonly class RegisterFingerprintDTO
{
    public function __construct(
        public string $deviceId,
        public string $userId,
        public string $fingerprintId,
        public string $fingerPosition,
        public string $templateData,
        public ?float $qualityScore = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            deviceId: $request->input('device_id'),
            userId: $request->input('user_id'),
            fingerprintId: $request->input('fingerprint_id'),
            fingerPosition: $request->input('finger_position'),
            templateData: $request->input('template_data'),
            qualityScore: $request->input('quality_score'),
        );
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'user_id' => $this->userId,
            'fingerprint_id' => $this->fingerprintId,
            'finger_position' => $this->fingerPosition,
            'template_data' => $this->templateData,
            'quality_score' => $this->qualityScore,
        ];
    }
}
