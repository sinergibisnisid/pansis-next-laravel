<?php

namespace App\Services;

use App\DTOs\Report\GenerateReportDTO;
use App\Enums\ReportFormat;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
    ) {}

    public function generate(GenerateReportDTO $dto): Report
    {
        $report = $this->reportRepository->create([
            'user_id' => $dto->userId,
            'branch_id' => $dto->branchId,
            'title' => $dto->title,
            'type' => $dto->type,
            'format' => $dto->format,
            'parameters' => $dto->parameters,
            'period_start' => $dto->periodStart,
            'period_end' => $dto->periodEnd,
            'status' => ReportStatus::Pending->value,
        ]);

        // Dispatch report generation to queue
        dispatch(function () use ($report) {
            $this->processReport($report);
        })->onQueue('reports');

        return $report;
    }

    public function generatePdf(Report $report): string
    {
        $data = $this->getReportData($report);

        $viewName = "reports.{$report->type}";

        // Fallback to generic template if specific one doesn't exist
        if (!view()->exists($viewName)) {
            $viewName = 'reports.generic';
        }

        $pdf = Pdf::loadView($viewName, [
            'report' => $report,
            'data' => $data,
            'title' => $report->title,
            'period_start' => $report->period_start,
            'period_end' => $report->period_end,
            'generated_at' => now()->toDateTimeString(),
        ]);

        $filename = $this->generateFilename($report, 'pdf');
        $path = "reports/{$report->user_id}/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function generateExcel(Report $report): string
    {
        $data = $this->getReportData($report);

        $filename = $this->generateFilename($report, 'xlsx');
        $path = "reports/{$report->user_id}/{$filename}";

        $export = new \App\Exports\ReportExport($report, $data);
        Excel::store($export, $path, 'local');

        return $path;
    }

    public function generateCsv(Report $report): string
    {
        $data = $this->getReportData($report);

        $filename = $this->generateFilename($report, 'csv');
        $path = "reports/{$report->user_id}/{$filename}";

        $csvContent = $this->buildCsvContent($data);
        Storage::disk('local')->put($path, $csvContent);

        return $path;
    }

    public function getReportData(Report $report): array
    {
        $parameters = $report->parameters ?? [];
        $periodStart = $report->period_start;
        $periodEnd = $report->period_end;
        $branchId = $report->branch_id;

        return match ($report->type) {
            'vault_access' => $this->getVaultAccessData($branchId, $periodStart, $periodEnd, $parameters),
            'alarm' => $this->getAlarmData($branchId, $periodStart, $periodEnd, $parameters),
            'device_health' => $this->getDeviceHealthData($branchId, $periodStart, $periodEnd, $parameters),
            'maintenance' => $this->getMaintenanceData($branchId, $periodStart, $periodEnd, $parameters),
            'audit' => $this->getAuditData($branchId, $periodStart, $periodEnd, $parameters),
            'user_activity' => $this->getUserActivityData($branchId, $periodStart, $periodEnd, $parameters),
            default => $this->getGeneralData($branchId, $periodStart, $periodEnd, $parameters),
        };
    }

    private function processReport(Report $report): void
    {
        try {
            $this->reportRepository->markGenerating($report->id);

            $filePath = match ($report->format) {
                'pdf' => $this->generatePdf($report),
                'excel' => $this->generateExcel($report),
                'csv' => $this->generateCsv($report),
                default => $this->generatePdf($report),
            };

            $this->reportRepository->markCompleted($report->id, $filePath);
        } catch (\Throwable $e) {
            $this->reportRepository->markFailed($report->id, $e->getMessage());
        }
    }

    private function getVaultAccessData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        $query = \App\Models\VaultSession::query()
            ->with(['vault', 'user']);

        if ($branchId) {
            $query->whereHas('vault', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($periodStart) {
            $query->where('opened_at', '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->where('opened_at', '<=', $periodEnd);
        }

        if (!empty($parameters['vault_id'])) {
            $query->where('vault_id', $parameters['vault_id']);
        }

        return [
            'title' => 'Vault Access Report',
            'records' => $query->orderBy('opened_at', 'desc')->get()->toArray(),
            'summary' => [
                'total_sessions' => $query->count(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ];
    }

    private function getAlarmData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        $query = \App\Models\AlarmLog::query()
            ->with(['vault']);

        if ($branchId) {
            $query->whereHas('vault', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($periodStart) {
            $query->where('created_at', '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->where('created_at', '<=', $periodEnd);
        }

        return [
            'title' => 'Alarm Report',
            'records' => $query->orderBy('created_at', 'desc')->get()->toArray(),
            'summary' => [
                'total_alarms' => $query->count(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ];
    }

    private function getDeviceHealthData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        $query = \App\Models\Device::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $devices = $query->get();

        return [
            'title' => 'Device Health Report',
            'records' => $devices->toArray(),
            'summary' => [
                'total_devices' => $devices->count(),
                'online' => $devices->where('status', 'online')->count(),
                'offline' => $devices->where('status', 'offline')->count(),
                'error' => $devices->where('status', 'error')->count(),
            ],
        ];
    }

    private function getMaintenanceData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        $query = \App\Models\MaintenancePlan::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($periodStart) {
            $query->where('scheduled_date', '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->where('scheduled_date', '<=', $periodEnd);
        }

        $plans = $query->orderBy('scheduled_date', 'desc')->get();

        return [
            'title' => 'Maintenance Report',
            'records' => $plans->toArray(),
            'summary' => [
                'total_plans' => $plans->count(),
                'completed' => $plans->where('status', 'completed')->count(),
                'overdue' => $plans->where('status', 'overdue')->count(),
                'scheduled' => $plans->where('status', 'scheduled')->count(),
            ],
        ];
    }

    private function getAuditData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        $query = \App\Models\AuditLog::query()
            ->with(['user']);

        if ($periodStart) {
            $query->where('created_at', '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->where('created_at', '<=', $periodEnd);
        }

        if (!empty($parameters['user_id'])) {
            $query->where('user_id', $parameters['user_id']);
        }

        if (!empty($parameters['event'])) {
            $query->where('event', $parameters['event']);
        }

        return [
            'title' => 'Audit Trail Report',
            'records' => $query->orderBy('created_at', 'desc')->get()->toArray(),
            'summary' => [
                'total_events' => $query->count(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ];
    }

    private function getUserActivityData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        $query = \App\Models\AuditLog::query()
            ->with(['user'])
            ->selectRaw('user_id, event, COUNT(*) as count')
            ->groupBy('user_id', 'event');

        if ($periodStart) {
            $query->where('created_at', '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->where('created_at', '<=', $periodEnd);
        }

        return [
            'title' => 'User Activity Report',
            'records' => $query->get()->toArray(),
            'summary' => [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ];
    }

    private function getGeneralData(?string $branchId, ?string $periodStart, ?string $periodEnd, array $parameters): array
    {
        return [
            'title' => 'General Report',
            'records' => [],
            'summary' => [
                'branch_id' => $branchId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'parameters' => $parameters,
            ],
        ];
    }

    private function generateFilename(Report $report, string $extension): string
    {
        $timestamp = now()->format('Ymd_His');
        $slug = Str::slug($report->title);

        return "{$slug}_{$timestamp}.{$extension}";
    }

    private function buildCsvContent(array $data): string
    {
        $records = $data['records'] ?? [];

        if (empty($records)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write headers
        $headers = array_keys($records[0]);
        fputcsv($output, $headers);

        // Write rows
        foreach ($records as $record) {
            $row = array_map(function ($value) {
                return is_array($value) ? json_encode($value) : $value;
            }, $record);
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }
}
