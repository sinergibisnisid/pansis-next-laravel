<?php

namespace App\DTOs\Maintenance;

use Illuminate\Http\Request;

readonly class CreateMaintenancePlanDTO
{
    public function __construct(
        public ?string $vaultId = null,
        public ?string $deviceId = null,
        public ?string $branchId = null,
        public ?string $assignedTo = null,
        public string $title = '',
        public ?string $description = null,
        public string $type = 'preventive',
        public string $priority = 'medium',
        public ?string $frequency = null,
        public ?string $scheduledDate = null,
        public ?string $scheduledTime = null,
        public ?string $dueDate = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vaultId: $request->input('vault_id'),
            deviceId: $request->input('device_id'),
            branchId: $request->input('branch_id'),
            assignedTo: $request->input('assigned_to'),
            title: $request->input('title'),
            description: $request->input('description'),
            type: $request->input('type', 'preventive'),
            priority: $request->input('priority', 'medium'),
            frequency: $request->input('frequency'),
            scheduledDate: $request->input('scheduled_date'),
            scheduledTime: $request->input('scheduled_time'),
            dueDate: $request->input('due_date'),
            notes: $request->input('notes'),
        );
    }

    public function toArray(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'device_id' => $this->deviceId,
            'branch_id' => $this->branchId,
            'assigned_to' => $this->assignedTo,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'frequency' => $this->frequency,
            'scheduled_date' => $this->scheduledDate,
            'scheduled_time' => $this->scheduledTime,
            'due_date' => $this->dueDate,
            'notes' => $this->notes,
        ];
    }
}
