<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly string $notificationLogId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $notificationLog = NotificationLog::findOrFail($this->notificationLogId);

        match ($notificationLog->channel) {
            NotificationChannel::WhatsApp => $this->sendWhatsApp($notificationLog),
            NotificationChannel::Email => $this->sendEmail($notificationLog),
            default => throw new \RuntimeException("Unsupported notification channel: {$notificationLog->channel->value}"),
        };

        $notificationLog->update([
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
        ]);

        Log::info('Notification sent successfully', [
            'notification_log_id' => $this->notificationLogId,
            'channel' => $notificationLog->channel->value,
            'recipient' => $notificationLog->recipient,
        ]);
    }

    protected function sendWhatsApp(NotificationLog $notificationLog): void
    {
        SendWhatsAppNotificationJob::dispatch(
            recipient: $notificationLog->recipient,
            title: $notificationLog->title,
            body: $notificationLog->body,
            notificationLogId: $notificationLog->id,
        );
    }

    protected function sendEmail(NotificationLog $notificationLog): void
    {
        Mail::raw($notificationLog->body, function ($message) use ($notificationLog) {
            $message->to($notificationLog->recipient)
                ->subject($notificationLog->title);
        });
    }

    public function failed(\Throwable $exception): void
    {
        $notificationLog = NotificationLog::find($this->notificationLogId);

        if ($notificationLog) {
            $notificationLog->update([
                'status' => NotificationStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);
        }

        Log::error('Notification sending failed', [
            'notification_log_id' => $this->notificationLogId,
            'error' => $exception->getMessage(),
        ]);
    }
}
