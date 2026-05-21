<?php

namespace App\Actions\Report;

use App\DTOs\Report\GenerateReportDTO;
use App\Models\Report;
use App\Services\ReportService;

class GenerateReportAction
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function execute(GenerateReportDTO $dto): Report
    {
        return $this->reportService->generate($dto);
    }
}
