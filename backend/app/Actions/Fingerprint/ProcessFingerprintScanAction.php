<?php

namespace App\Actions\Fingerprint;

use App\DTOs\Fingerprint\FingerprintScanDTO;
use App\DTOs\Vault\VaultAccessDTO;
use App\Events\FingerprintScanned;
use App\Events\UnauthorizedAccessAttempt;
use App\Repositories\Contracts\FingerprintRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\VaultService;

class ProcessFingerprintScanAction
{
    public function __construct(
        private readonly VaultService $vaultService,
        private readonly FingerprintRepositoryInterface $fingerprintRepository,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(FingerprintScanDTO $dto): array
    {
        $isValid = $this->fingerprintRepository->validateFingerprint(
            $dto->fingerprintId,
            $dto->userId,
        );

        if ($isValid) {
            event(new FingerprintScanned(
                deviceId: $dto->deviceId,
                userId: $dto->userId,
                vaultId: $dto->vaultId,
                scanResult: 'success',
                scannedAt: now(),
            ));

            $vaultAccessDto = new VaultAccessDTO(
                vaultId: $dto->vaultId,
                userId: $dto->userId,
                deviceId: $dto->deviceId,
                accessType: 'fingerprint',
                fingerprintDeviceId: $dto->fingerprintId,
                confidenceScore: $dto->confidenceScore,
                ipAddress: $dto->ipAddress,
            );

            $sessionData = $this->vaultService->openVault($vaultAccessDto);

            return [
                'status' => 'granted',
                'vault' => $sessionData['vault'],
                'session' => $sessionData['session'],
            ];
        }

        event(new FingerprintScanned(
            deviceId: $dto->deviceId,
            userId: $dto->userId,
            vaultId: $dto->vaultId,
            scanResult: 'failed',
            scannedAt: now(),
        ));

        event(new UnauthorizedAccessAttempt(
            vaultId: $dto->vaultId,
            branchId: $this->resolveBranchId($dto->vaultId),
            deviceId: $dto->deviceId,
            userId: $dto->userId,
            reason: 'Fingerprint validation failed',
            attemptedAt: now(),
        ));

        $this->notificationService->sendUnauthorizedAccessAlert(
            $dto->vaultId,
            $dto->userId,
            'Fingerprint validation failed',
        );

        return [
            'status' => 'denied',
            'reason' => 'Fingerprint validation failed',
        ];
    }

    private function resolveBranchId(string $vaultId): string
    {
        $vault = \App\Models\Vault::find($vaultId);

        return $vault?->branch_id ?? '';
    }
}
