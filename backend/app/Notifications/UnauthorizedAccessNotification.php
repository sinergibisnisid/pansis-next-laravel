<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class UnauthorizedAccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $vaultId,
        public readonly string $branchId,
        public readonly string $reason,
        public readonly Carbon $attemptedAt
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[SECURITY ALERT] Unauthorized Access Attempt Detected')
            ->greeting('Unauthorized Access Alert')
            ->line('An unauthorized access attempt has been detected.')
            ->line("**Vault ID:** {$this->vaultId}")
            ->line("**Branch ID:** {$this->branchId}")
            ->line("**Reason:** {$this->reason}")
            ->line("**Attempted At:** {$this->attemptedAt->format('d M Y H:i:s')}")
            ->action('View Security Logs', url("/security/access-logs"))
            ->line('Immediate investigation is recommended.')
            ->salutation('PANSIN ACCESS Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'unauthorized_access',
            'vault_id' => $this->vaultId,
            'branch_id' => $this->branchId,
            'reason' => $this->reason,
            'attempted_at' => $this->attemptedAt->toISOString(),
        ];
    }
}
