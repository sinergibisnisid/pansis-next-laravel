<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\NotificationLogRepository;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly NotificationLogRepository $notificationLogRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['channel', 'status', 'date_from', 'date_to']);
        $perPage = $request->integer('per_page', 15);

        $notifications = $this->notificationLogRepository->paginate(
            $request->user()->id,
            $filters,
            $perPage,
        );

        return $this->paginatedResponse($notifications, 'Notifications retrieved successfully');
    }

    public function configs(Request $request): JsonResponse
    {
        $configs = $this->notificationService->getUserConfigs($request->user());

        return $this->successResponse($configs, 'Notification configurations retrieved');
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:email,sms,whatsapp,push,telegram',
            'event_type' => 'required|string',
            'is_enabled' => 'required|boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
        ]);

        $config = $this->notificationService->updateConfig($request->user(), $data);

        return $this->successResponse($config, 'Notification configuration updated');
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->notificationLogRepository->findOrFail($id);

        if ($notification->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $notification = $this->notificationLogRepository->markAsRead($notification);

        return $this->successResponse($notification, 'Notification marked as read');
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'channel' => 'required|string|in:email,sms,whatsapp,push,telegram',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
        ]);

        $result = $this->notificationService->send($data);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['notification'], 'Notification sent successfully');
    }
}
