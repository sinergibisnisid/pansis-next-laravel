<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $user,
        public readonly string $ipAddress,
        public readonly int $failedAttempts
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[SECURITY] Suspicious Login Activity Detected')
            ->greeting('Suspicious Login Alert')
            ->line('Suspicious login activity has been detected on your account.')
            ->line("**User:** {$this->user->full_name} ({$this->user->username})")
            ->line("**Email:** {$this->user->email}")
            ->line("**IP Address:** {$this->ipAddress}")
            ->line("**Failed Attempts:** {$this->failedAttempts}")
            ->line("**Time:** " . now()->format('d M Y H:i:s'))
            ->line('If this was not you, please contact your administrator immediately and change your password.')
            ->action('Review Security Settings', url('/security/settings'))
            ->line('Your account may be temporarily locked for security purposes.')
            ->salutation('PANSIN ACCESS Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'suspicious_login',
            'user_id' => $this->user->id,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'ip_address' => $this->ipAddress,
            'failed_attempts' => $this->failedAttempts,
            'detected_at' => now()->toISOString(),
        ];
    }
}
