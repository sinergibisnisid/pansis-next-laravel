<?php

namespace App\DTOs\Report;

use Illuminate\Http\Request;

readonly class GenerateReportDTO
{
    public function __construct(
        public string $userId,
        public ?string $branchId = null,
        public string $title = '',
        public string $type = 'general',
        public string $format = 'pdf',
        public ?array $parameters = null,
        public ?string $periodStart = null,
        public ?string $periodEnd = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->input('user_id'),
            branchId: $request->input('branch_id'),
            title: $request->input('title'),
            type: $request->input('type', 'general'),
            format: $request->input('format', 'pdf'),
            parameters: $request->input('parameters'),
            periodStart: $request->input('period_start'),
            periodEnd: $request->input('period_end'),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'branch_id' => $this->branchId,
            'title' => $this->title,
            'type' => $this->type,
            'format' => $this->format,
            'parameters' => $this->parameters,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
        ];
    }
}
