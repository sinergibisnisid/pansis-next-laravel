<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly string $recipient,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $notificationLogId = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $gatewayUrl = config('services.whatsapp.gateway_url');
        $apiKey = config('services.whatsapp.api_key');

        $message = "*{$this->title}*\n\n{$this->body}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($gatewayUrl, [
            'phone' => $this->recipient,
            'message' => $message,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "WhatsApp gateway returned HTTP {$response->status()}: {$response->body()}"
            );
        }

        if ($this->notificationLogId) {
            NotificationLog::where('id', $this->notificationLogId)->update([
                'status' => NotificationStatus::Sent,
                'sent_at' => now(),
            ]);
        }

        Log::info('WhatsApp notification sent successfully', [
            'recipient' => $this->recipient,
            'notification_log_id' => $this->notificationLogId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        if ($this->notificationLogId) {
            NotificationLog::where('id', $this->notificationLogId)->update([
                'status' => NotificationStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);
        }

        Log::error('WhatsApp notification failed', [
            'recipient' => $this->recipient,
            'notification_log_id' => $this->notificationLogId,
            'error' => $exception->getMessage(),
        ]);
    }
}
