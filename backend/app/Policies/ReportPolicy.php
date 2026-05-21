<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reports.view');
    }

    public function view(User $user, Report $report): bool
    {
        if (!$user->hasPermissionTo('reports.view')) {
            return false;
        }

        // Admin or Super Admin can view all reports
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Others can only view their own reports
        return $user->id === $report->user_id;
    }

    public function generate(User $user): bool
    {
        return $user->hasPermissionTo('reports.generate');
    }

    public function download(User $user, Report $report): bool
    {
        if (!$user->hasPermissionTo('reports.download')) {
            return false;
        }

        // Admin or Super Admin can download all reports
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Others can only download their own reports
        return $user->id === $report->user_id;
    }

    public function schedule(User $user): bool
    {
        return $user->hasPermissionTo('reports.schedule');
    }
}
