<?php

namespace App\Observers;

use App\Enums\VaultStatus;
use App\Models\VaultSession;

class VaultSessionObserver
{
    public function created(VaultSession $session): void
    {
        // Update vault status to unlocked when a session is created
        $session->vault()->update([
            'status' => VaultStatus::Unlocked,
        ]);
    }

    public function updated(VaultSession $session): void
    {
        // If closed_at is set, update vault status to locked
        if ($session->wasChanged('closed_at') && $session->closed_at !== null) {
            $session->vault()->update([
                'status' => VaultStatus::Locked,
            ]);
        }
    }
}
