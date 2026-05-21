<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly string $reportId,
    ) {
        $this->onQueue('reports');
    }

    public function handle(ReportService $reportService): void
    {
        $report = Report::findOrFail($this->reportId);

        $report->update([
            'status' => ReportStatus::Generating,
        ]);

        $reportService->generate($report);

        Log::info('Report generated successfully', [
            'report_id' => $this->reportId,
            'type' => $report->type->value,
            'format' => $report->format->value,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $report = Report::find($this->reportId);

        if ($report) {
            $report->update([
                'status' => ReportStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);
        }

        Log::error('Report generation failed', [
            'report_id' => $this->reportId,
            'error' => $exception->getMessage(),
        ]);
    }
}
