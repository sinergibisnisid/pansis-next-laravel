<?php

namespace App\Notifications;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DeviceOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Device $device,
        public readonly Carbon $lastHeartbeatAt
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $device = $this->device;
        $offlineDuration = $this->lastHeartbeatAt->diffForHumans(now(), true);

        return (new MailMessage)
            ->subject("[DEVICE OFFLINE] {$device->name} - No Heartbeat Detected")
            ->greeting('Device Offline Alert')
            ->line('A device has gone offline and is no longer sending heartbeat signals.')
            ->line("**Device:** {$device->name}")
            ->line("**Serial Number:** {$device->serial_number}")
            ->line("**Type:** {$device->type->value}")
            ->line("**IP Address:** {$device->ip_address}")
            ->line("**Branch:** {$device->branch?->name}")
            ->line("**Vault:** {$device->vault?->name}")
            ->line("**Last Heartbeat:** {$this->lastHeartbeatAt->format('d M Y H:i:s')}")
            ->line("**Offline Duration:** {$offlineDuration}")
            ->action('View Device Status', url("/devices/{$device->id}"))
            ->line('Please check the device connectivity and status.')
            ->salutation('PANSIN ACCESS Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'device_offline',
            'device_id' => $this->device->id,
            'device_name' => $this->device->name,
            'device_type' => $this->device->type->value,
            'serial_number' => $this->device->serial_number,
            'vault_id' => $this->device->vault_id,
            'branch_id' => $this->device->branch_id,
            'ip_address' => $this->device->ip_address,
            'last_heartbeat_at' => $this->lastHeartbeatAt->toISOString(),
        ];
    }
}
