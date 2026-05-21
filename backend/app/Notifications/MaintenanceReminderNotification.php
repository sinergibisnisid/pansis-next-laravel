<?php

namespace App\Notifications;

use App\Models\MaintenancePlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MaintenancePlan $maintenancePlan
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->maintenancePlan;

        return (new MailMessage)
            ->subject("[MAINTENANCE] Reminder: {$plan->title}")
            ->greeting('Maintenance Reminder')
            ->line("You have a scheduled maintenance task that requires attention.")
            ->line("**Title:** {$plan->title}")
            ->line("**Type:** {$plan->type->value}")
            ->line("**Priority:** {$plan->priority->value}")
            ->line("**Branch:** {$plan->branch?->name}")
            ->line("**Vault:** {$plan->vault?->name}")
            ->line("**Device:** {$plan->device?->name}")
            ->line("**Scheduled Date:** {$plan->scheduled_date->format('d M Y')}")
            ->line("**Due Date:** {$plan->due_date?->format('d M Y')}")
            ->line("**Description:** {$plan->description}")
            ->action('View Maintenance Plan', url("/maintenance/{$plan->id}"))
            ->line('Please complete this maintenance task before the due date.')
            ->salutation('PANSIN ACCESS Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance_reminder',
            'maintenance_plan_id' => $this->maintenancePlan->id,
            'title' => $this->maintenancePlan->title,
            'maintenance_type' => $this->maintenancePlan->type->value,
            'priority' => $this->maintenancePlan->priority->value,
            'status' => $this->maintenancePlan->status->value,
            'vault_id' => $this->maintenancePlan->vault_id,
            'device_id' => $this->maintenancePlan->device_id,
            'branch_id' => $this->maintenancePlan->branch_id,
            'scheduled_date' => $this->maintenancePlan->scheduled_date?->toISOString(),
            'due_date' => $this->maintenancePlan->due_date?->toISOString(),
        ];
    }
}
