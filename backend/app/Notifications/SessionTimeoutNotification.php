<?php

namespace App\Notifications;

use App\Models\VaultSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionTimeoutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly VaultSession $vaultSession
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $session = $this->vaultSession;
        $durationMinutes = intval($session->duration_seconds / 60);
        $maxMinutes = intval($session->max_duration_seconds / 60);

        return (new MailMessage)
            ->subject('[ALERT] Vault Session Timeout Detected')
            ->greeting('Session Timeout Alert')
            ->line('A vault session has exceeded its maximum allowed duration.')
            ->line("**Vault:** {$session->vault?->name} ({$session->vault?->code})")
            ->line("**User:** {$session->user?->full_name}")
            ->line("**Opened At:** {$session->opened_at->format('d M Y H:i:s')}")
            ->line("**Duration:** {$durationMinutes} minutes")
            ->line("**Max Allowed:** {$maxMinutes} minutes")
            ->line("**Status:** {$session->status->value}")
            ->action('View Session Details', url("/vault-sessions/{$session->id}"))
            ->line('Please investigate this session timeout immediately.')
            ->salutation('PANSIN ACCESS Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_timeout',
            'vault_session_id' => $this->vaultSession->id,
            'vault_id' => $this->vaultSession->vault_id,
            'user_id' => $this->vaultSession->user_id,
            'opened_at' => $this->vaultSession->opened_at?->toISOString(),
            'duration_seconds' => $this->vaultSession->duration_seconds,
            'max_duration_seconds' => $this->vaultSession->max_duration_seconds,
            'status' => $this->vaultSession->status->value,
        ];
    }
}
