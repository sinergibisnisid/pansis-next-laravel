<?php

namespace App\DTOs\Device;

use Illuminate\Http\Request;

readonly class RegisterDeviceDTO
{
    public function __construct(
        public string $vaultId,
        public string $branchId,
        public string $name,
        public string $serialNumber,
        public string $type,
        public ?string $ipAddress = null,
        public ?string $macAddress = null,
        public ?string $firmwareVersion = null,
        public ?string $deviceToken = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vaultId: $request->input('vault_id'),
            branchId: $request->input('branch_id'),
            name: $request->input('name'),
            serialNumber: $request->input('serial_number'),
            type: $request->input('type'),
            ipAddress: $request->input('ip_address'),
            macAddress: $request->input('mac_address'),
            firmwareVersion: $request->input('firmware_version'),
            deviceToken: $request->input('device_token'),
            metadata: $request->input('metadata'),
        );
    }

    public function toArray(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'branch_id' => $this->branchId,
            'name' => $this->name,
            'serial_number' => $this->serialNumber,
            'type' => $this->type,
            'ip_address' => $this->ipAddress,
            'mac_address' => $this->macAddress,
            'firmware_version' => $this->firmwareVersion,
            'device_token' => $this->deviceToken,
            'metadata' => $this->metadata,
        ];
    }
}
