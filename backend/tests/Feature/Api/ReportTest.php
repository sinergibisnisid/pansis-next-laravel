<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Report;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\ReportFormat;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
});

describe('Report Generation', function () {
    test('generate report', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/reports', [
            'type' => 'vault_access',
            'format' => 'pdf',
            'branch_id' => $this->branch->id,
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'type', 'status'],
            ]);

        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'type' => 'vault_access',
        ]);
    });

    test('download report', function () {
        $user = $this->authenticateSuperAdmin();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'status' => ReportStatus::Completed,
            'file_path' => 'reports/test-report.pdf',
        ]);

        $response = $this->getJson("/api/reports/{$report->id}/download");

        // Should return file or redirect to download URL
        $response->assertOk();
    });
});

describe('Report Listing', function () {
    test('list reports with filtering', function () {
        $user = $this->authenticateSuperAdmin();

        Report::factory()->count(5)->create([
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'type' => ReportType::VaultAccess,
            'status' => ReportStatus::Completed,
        ]);

        Report::factory()->count(3)->create([
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'type' => ReportType::DeviceHealth,
            'status' => ReportStatus::Pending,
        ]);

        $response = $this->getJson('/api/reports?type=vault_access');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('scheduled reports', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/reports/schedule', [
            'type' => 'vault_access',
            'format' => 'pdf',
            'branch_id' => $this->branch->id,
            'schedule' => 'weekly',
            'day_of_week' => 1,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });
});
