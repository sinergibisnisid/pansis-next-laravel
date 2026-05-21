<?php

namespace App\DTOs\WorkingTime;

use Illuminate\Http\Request;

readonly class CreateWorkingTimeDTO
{
    public function __construct(
        public string $branchId,
        public ?string $vaultId = null,
        public string $name = '',
        public string $type = 'regular',
        public ?int $dayOfWeek = null,
        public ?string $specificDate = null,
        public string $startTime = '08:00',
        public string $endTime = '17:00',
        public string $timezone = 'Asia/Jakarta',
        public bool $isHoliday = false,
        public ?string $description = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            branchId: $request->input('branch_id'),
            vaultId: $request->input('vault_id'),
            name: $request->input('name'),
            type: $request->input('type', 'regular'),
            dayOfWeek: $request->input('day_of_week'),
            specificDate: $request->input('specific_date'),
            startTime: $request->input('start_time', '08:00'),
            endTime: $request->input('end_time', '17:00'),
            timezone: $request->input('timezone', 'Asia/Jakarta'),
            isHoliday: $request->boolean('is_holiday', false),
            description: $request->input('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'branch_id' => $this->branchId,
            'vault_id' => $this->vaultId,
            'name' => $this->name,
            'type' => $this->type,
            'day_of_week' => $this->dayOfWeek,
            'specific_date' => $this->specificDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'timezone' => $this->timezone,
            'is_holiday' => $this->isHoliday,
            'description' => $this->description,
        ];
    }
}
