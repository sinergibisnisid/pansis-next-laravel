<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\EventType;
use App\Enums\NotificationChannel;
use App\Models\NotificationConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller konfigurasi notifikasi per cabang
class NotificationConfigController extends Controller
{
    // List konfigurasi notifikasi + filter
    public function index(Request $request): JsonResponse
    {
        $query = NotificationConfig::query()
            ->with(['user', 'branch']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->has('is_enabled')) {
            $query->where('is_enabled', filter_var($request->input('is_enabled'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->integer('per_page', 15);
        $configs = $query->orderBy('branch_id')->orderBy('event_type')->paginate($perPage);

        return $this->paginatedResponse($configs, 'Notification configs retrieved');
    }

    // Detail satu config
    public function show(string $id): JsonResponse
    {
        $config = NotificationConfig::with(['user', 'branch'])->findOrFail($id);

        return $this->successResponse($config, 'Notification config retrieved');
    }

    // Buat config baru
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'branch_id' => 'required|uuid|exists:branches,id',
            'event_type' => 'required|string|in:' . implode(',', EventType::values()),
            'channel' => 'required|string|in:' . implode(',', NotificationChannel::values()),
            'is_enabled' => 'boolean',
            'recipients' => 'nullable|array',
            'recipients.*' => 'string|max:255',
            'schedule' => 'nullable|array',
            'schedule.quiet_hours_start' => 'nullable|date_format:H:i',
            'schedule.quiet_hours_end' => 'nullable|date_format:H:i',
            'schedule.days' => 'nullable|array',
            'schedule.days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'template' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
        ]);

        // Default ke user yang login
        $data['user_id'] = $data['user_id'] ?? $request->user()->id;

        $config = NotificationConfig::create($data);
        $config->load(['user', 'branch']);

        return $this->successResponse($config, 'Notification config created', 201);
    }

    // Update config
    public function update(Request $request, string $id): JsonResponse
    {
        $config = NotificationConfig::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'branch_id' => 'sometimes|uuid|exists:branches,id',
            'event_type' => 'sometimes|string|in:' . implode(',', EventType::values()),
            'channel' => 'sometimes|string|in:' . implode(',', NotificationChannel::values()),
            'is_enabled' => 'boolean',
            'recipients' => 'nullable|array',
            'recipients.*' => 'string|max:255',
            'schedule' => 'nullable|array',
            'schedule.quiet_hours_start' => 'nullable|date_format:H:i',
            'schedule.quiet_hours_end' => 'nullable|date_format:H:i',
            'schedule.days' => 'nullable|array',
            'schedule.days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'template' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
        ]);

        $config->update($data);
        $config->load(['user', 'branch']);

        return $this->successResponse($config, 'Notification config updated');
    }

    // Hapus config
    public function destroy(string $id): JsonResponse
    {
        $config = NotificationConfig::findOrFail($id);
        $config->delete();

        return $this->successResponse(message: 'Notification config deleted');
    }

    // Toggle aktif/nonaktif
    public function toggle(string $id): JsonResponse
    {
        $config = NotificationConfig::findOrFail($id);
        $config->update(['is_enabled' => !$config->is_enabled]);

        return $this->successResponse($config->fresh(), 'Notification config toggled');
    }

    // Bulk create config untuk satu cabang
    public function bulkStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|uuid|exists:branches,id',
            'configs' => 'required|array|min:1|max:50',
            'configs.*.user_id' => 'nullable|uuid|exists:users,id',
            'configs.*.event_type' => 'required|string|in:' . implode(',', EventType::values()),
            'configs.*.channel' => 'required|string|in:' . implode(',', NotificationChannel::values()),
            'configs.*.is_enabled' => 'boolean',
            'configs.*.recipients' => 'nullable|array',
            'configs.*.recipients.*' => 'string|max:255',
            'configs.*.schedule' => 'nullable|array',
            'configs.*.template' => 'nullable|string|max:2000',
        ]);

        $created = [];
        foreach ($data['configs'] as $configData) {
            $configData['branch_id'] = $data['branch_id'];
            $configData['user_id'] = $configData['user_id'] ?? $request->user()->id;
            $created[] = NotificationConfig::create($configData);
        }

        return $this->successResponse($created, count($created) . ' notification configs created', 201);
    }

    // Opsi event type dan channel (untuk dropdown UI)
    public function options(): JsonResponse
    {
        return $this->successResponse([
            'event_types' => collect(EventType::cases())->map(fn ($e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ]),
            'channels' => collect(NotificationChannel::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ], 'Notification config options retrieved');
    }

    // Config dikelompokkan per cabang (overview admin)
    public function byBranch(Request $request): JsonResponse
    {
        $organizationId = $request->input('organization_id');

        $query = NotificationConfig::query()
            ->with('branch')
            ->select('branch_id')
            ->selectRaw('COUNT(*) as total_configs')
            ->selectRaw('SUM(CASE WHEN is_enabled = 1 THEN 1 ELSE 0 END) as enabled_configs')
            ->groupBy('branch_id');

        if ($organizationId) {
            $query->whereHas('branch', fn ($q) => $q->where('organization_id', $organizationId));
        }

        $grouped = $query->get();

        return $this->successResponse($grouped, 'Notification configs by branch retrieved');
    }
}