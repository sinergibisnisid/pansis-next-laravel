<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\CreateBranchRequest;
use App\Repositories\BranchRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchRepository $branchRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['organization_id', 'search', 'city', 'province']);
        $perPage = $request->integer('per_page', 15);

        $branches = $this->branchRepository->paginate($filters, $perPage);

        return $this->paginatedResponse($branches, 'Branches retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $branch = $this->branchRepository->findOrFail($id);
        $branch->load(['organization', 'vaults', 'devices', 'users']);

        return $this->successResponse($branch, 'Branch retrieved successfully');
    }

    public function store(CreateBranchRequest $request): JsonResponse
    {
        $branch = $this->branchRepository->create($request->validated());
        $branch->load(['organization']);

        return $this->successResponse($branch, 'Branch created successfully', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $branch = $this->branchRepository->findOrFail($id);

        $data = $request->validate([
            'organization_id' => 'sometimes|uuid|exists:organizations,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'timezone' => 'nullable|string|timezone',
        ]);

        $branch = $this->branchRepository->update($branch, $data);
        $branch->load(['organization']);

        return $this->successResponse($branch, 'Branch updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $branch = $this->branchRepository->findOrFail($id);
        $this->branchRepository->delete($branch);

        return $this->successResponse(message: 'Branch deleted successfully');
    }
}
