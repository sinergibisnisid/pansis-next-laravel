<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'role', 'status', 'search', 'organization_id']);
        $perPage = $request->integer('per_page', 15);

        $users = $this->userRepository->paginate($filters, $perPage);

        return $this->paginatedResponse($users, 'Users retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->userRepository->findOrFail($id);
        $user->load(['roles', 'branch', 'organization']);

        return $this->successResponse($user, 'User retrieved successfully');
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->create($data);

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        $user->load(['roles', 'branch', 'organization']);

        return $this->successResponse($user, 'User created successfully', 201);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->userRepository->findOrFail($id);
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->update($user, $data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $user->load(['roles', 'branch', 'organization']);

        return $this->successResponse($user, 'User updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $user = $this->userRepository->findOrFail($id);
        $this->userRepository->delete($user);

        return $this->successResponse(message: 'User deleted successfully');
    }

    public function updatePassword(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->userRepository->findOrFail($id);

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return $this->errorResponse('Current password is incorrect', 422);
        }

        $this->userRepository->update($user, [
            'password' => Hash::make($request->input('password')),
        ]);

        return $this->successResponse(message: 'Password updated successfully');
    }

    public function toggleActive(string $id): JsonResponse
    {
        $user = $this->userRepository->findOrFail($id);

        $user = $this->userRepository->update($user, [
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return $this->successResponse($user, "User {$status} successfully");
    }
}
