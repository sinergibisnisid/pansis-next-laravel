<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccess
{
    /**
     * Check if authenticated user has access to the requested branch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        // Super Admin and Admin Pusat can access all branches
        if ($user->hasRole(['Super Admin', 'Admin Pusat'])) {
            return $next($request);
        }

        // Determine the branch being accessed
        $branchId = $this->resolveBranchId($request);

        if (!$branchId) {
            return $next($request);
        }

        // Admin Cabang, Operator, Security, Maintenance can only access their own branch
        if ($user->branch_id && (int) $user->branch_id !== (int) $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this branch.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Resolve the branch ID from the request.
     */
    protected function resolveBranchId(Request $request): ?int
    {
        // Check route parameter
        if ($request->route('branch')) {
            $branch = $request->route('branch');
            return is_object($branch) ? $branch->id : (int) $branch;
        }

        // Check query parameter
        if ($request->has('branch_id')) {
            return (int) $request->input('branch_id');
        }

        // Check request body
        if ($request->has('branch_id')) {
            return (int) $request->input('branch_id');
        }

        return null;
    }
}
