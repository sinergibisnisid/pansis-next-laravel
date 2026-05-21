<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Report\GenerateReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Repositories\ReportRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportRepository $reportRepository,
        private readonly GenerateReportAction $generateReportAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'format', 'status', 'branch_id', 'date_from', 'date_to']);
        $perPage = $request->integer('per_page', 15);

        $reports = $this->reportRepository->paginate($filters, $perPage);

        return $this->paginatedResponse($reports, 'Reports retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $report = $this->reportRepository->findOrFail($id);
        $report->load(['generatedBy', 'branch']);

        return $this->successResponse($report, 'Report retrieved successfully');
    }

    public function generate(GenerateReportRequest $request): JsonResponse
    {
        $result = $this->generateReportAction->execute(
            $request->validated(),
            $request->user(),
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['report'], 'Report generation started', 202);
    }

    public function download(string $id): BinaryFileResponse|JsonResponse
    {
        $report = $this->reportRepository->findOrFail($id);

        if ($report->status !== 'completed') {
            return $this->errorResponse('Report is not ready for download', 422);
        }

        if (!$report->file_path || !file_exists(storage_path("app/{$report->file_path}"))) {
            return $this->errorResponse('Report file not found', 404);
        }

        return response()->download(
            storage_path("app/{$report->file_path}"),
            $report->title . '.' . $report->format,
        );
    }

    public function scheduled(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'type']);
        $reports = $this->reportRepository->getScheduled($filters);

        return $this->successResponse($reports, 'Scheduled reports retrieved');
    }
}
