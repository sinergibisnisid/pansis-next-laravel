<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\OrganizationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        $perPage = $request->integer('per_page', 15);

        $organizations = $this->organizationRepository->paginate($filters, $perPage);

        return $this->paginatedResponse($organizations, 'Organizations retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $organization = $this->organizationRepository->findOrFail($id);
        $organization->load(['branches']);

        return $this->successResponse($organization, 'Organization retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:organizations,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'logo' => 'nullable|string',
        ]);

        $organization = $this->organizationRepository->create($data);

        return $this->successResponse($organization, 'Organization created successfully', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $organization = $this->organizationRepository->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:organizations,code,' . $organization->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'logo' => 'nullable|string',
        ]);

        $organization = $this->organizationRepository->update($organization, $data);

        return $this->successResponse($organization, 'Organization updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $organization = $this->organizationRepository->findOrFail($id);
        $this->organizationRepository->delete($organization);

        return $this->successResponse(message: 'Organization deleted successfully');
    }
}
