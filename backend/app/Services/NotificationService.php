<?php

namespace App\Services;

use App\DTOs\Notification\SendNotificationDTO;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\AlarmLog;
use App\Models\MaintenancePlan;
use App\Models\NotificationConfig;
use App\Models\NotificationLog;
use App\Models\User;
use App\Repositories\Contracts\NotificationLogRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    public function __construct(
        private readonly NotificationLogRepositoryInterface $notificationLogRepository,
    ) {}

    public function send(SendNotificationDTO $dto): void
    {
        dispatch(function () use ($dto) {
            $recipient = $dto->recipient;

            // If no explicit recipient, resolve from config
            if (!$recipient && $dto->branchId) {
                $recipients = $this->getConfiguredRecipients($dto->type, $dto->branchId);
                foreach ($recipients as $recipientAddress) {
                    $this->dispatchToChannel($dto->channel, $recipientAddress, $dto->title, $dto->body);
                    $this->saveLog($dto, $recipientAddress, NotificationStatus::Sent);
                }
                return;
            }

            if ($recipient) {
                $this->dispatchToChannel($dto->channel, $recipient, $dto->title, $dto->body);
                $this->saveLog($dto, $recipient, NotificationStatus::Sent);
            }
        })->onQueue('notifications');
    }

    public function sendWhatsApp(string $recipient, string $title, string $body): bool
    {
        try {
            $gatewayUrl = config('services.whatsapp.gateway_url');
            $apiKey = config('services.whatsapp.api_key');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post($gatewayUrl, [
                'phone' => $recipient,
                'message' => "{$title}\n\n{$body}",
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("WhatsApp notification failed: {$e->getMessage()}", [
                'recipient' => $recipient,
                'title' => $title,
            ]);
            return false;
        }
    }

    public function sendEmail(string $recipient, string $title, string $body): bool
    {
        try {
            Mail::raw($body, function ($message) use ($recipient, $title) {
                $message->to($recipient)
                    ->subject($title);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error("Email notification failed: {$e->getMessage()}", [
                'recipient' => $recipient,
                'title' => $title,
            ]);
            return false;
        }
    }

    public function sendAlarmNotification(AlarmLog $alarm): void
    {
        $branchId = $alarm->vault?->branch_id;
        $recipients = $this->getConfiguredRecipients('alarm', $branchId);

        $title = "ALARM: {$alarm->alarm_type} - Vault {$alarm->vault_id}";
        $body = "An alarm has been triggered.\n\n"
            . "Type: {$alarm->alarm_type}\n"
            . "Vault: {$alarm->vault_id}\n"
            . "Severity: {$alarm->severity}\n"
            . "Time: " . now()->toDateTimeString() . "\n"
            . "Description: {$alarm->description}";

        foreach ($recipients as $recipient) {
            $this->sendWhatsApp($recipient, $title, $body);
            $this->sendEmail($recipient, $title, $body);
        }
    }

    public function sendMaintenanceReminder(MaintenancePlan $plan): void
    {
        $branchId = $plan->branch_id;
        $recipients = $this->getConfiguredRecipients('maintenance', $branchId);

        $title = "Maintenance Reminder: {$plan->title}";
        $body = "A maintenance task is upcoming.\n\n"
            . "Title: {$plan->title}\n"
            . "Type: {$plan->type}\n"
            . "Priority: {$plan->priority}\n"
            . "Scheduled Date: {$plan->scheduled_date}\n"
            . "Description: {$plan->description}";

        foreach ($recipients as $recipient) {
            $this->sendEmail($recipient, $title, $body);
        }
    }

    public function sendSuspiciousLoginAlert(User $user, string $ipAddress): void
    {
        $title = "Security Alert: Suspicious Login Attempt";
        $body = "Multiple failed login attempts detected.\n\n"
            . "User: {$user->username}\n"
            . "IP Address: {$ipAddress}\n"
            . "Time: " . now()->toDateTimeString() . "\n"
            . "Account has been locked for 30 minutes.";

        // Send to user's email
        if ($user->email) {
            $this->sendEmail($user->email, $title, $body);
        }

        // Send to admin recipients
        $recipients = $this->getConfiguredRecipients('security', null);
        foreach ($recipients as $recipient) {
            $this->sendEmail($recipient, $title, $body);
        }
    }

    public function sendUnauthorizedAccessAlert(string $vaultId, ?string $userId, string $reason): void
    {
        $vault = \App\Models\Vault::find($vaultId);
        $user = $userId ? User::find($userId) : null;
        $branchId = $vault?->branch_id;

        $title = "SECURITY: Unauthorized Access Attempt";
        $body = "An unauthorized access attempt was detected.\n\n"
            . "Vault: {$vaultId}\n"
            . "User: " . ($user?->username ?? 'Unknown') . "\n"
            . "Reason: {$reason}\n"
            . "Time: " . now()->toDateTimeString();

        $recipients = $this->getConfiguredRecipients('security', $branchId);

        foreach ($recipients as $recipient) {
            $this->sendWhatsApp($recipient, $title, $body);
            $this->sendEmail($recipient, $title, $body);
        }
    }

    public function getConfiguredRecipients(string $eventType, ?string $branchId): array
    {
        $query = NotificationConfig::where('event_type', $eventType)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $configs = $query->get();

        $recipients = [];
        foreach ($configs as $config) {
            if (!empty($config->recipients)) {
                $configRecipients = is_array($config->recipients)
                    ? $config->recipients
                    : json_decode($config->recipients, true);

                if (is_array($configRecipients)) {
                    $recipients = array_merge($recipients, $configRecipients);
                }
            }
        }

        return array_unique($recipients);
    }

    private function dispatchToChannel(string $channel, string $recipient, string $title, string $body): void
    {
        match ($channel) {
            'whatsapp' => $this->sendWhatsApp($recipient, $title, $body),
            'email' => $this->sendEmail($recipient, $title, $body),
            default => $this->sendEmail($recipient, $title, $body),
        };
    }

    private function saveLog(SendNotificationDTO $dto, string $recipient, NotificationStatus $status): void
    {
        $this->notificationLogRepository->create([
            'user_id' => $dto->userId,
            'branch_id' => $dto->branchId,
            'channel' => $dto->channel,
            'type' => $dto->type,
            'title' => $dto->title,
            'body' => $dto->body,
            'recipient' => $recipient,
            'status' => $status->value,
            'metadata' => $dto->metadata,
            'sent_at' => now(),
        ]);
    }
}
