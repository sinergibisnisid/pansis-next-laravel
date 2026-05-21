<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'event' => 'user_created',
            'description' => "User '{$user->full_name}' ({$user->username}) was created.",
            'properties' => [
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'branch_id' => $user->branch_id,
                'organization_id' => $user->organization_id,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function updated(User $user): void
    {
        $importantFields = [
            'email',
            'username',
            'full_name',
            'phone',
            'is_active',
            'branch_id',
            'organization_id',
        ];

        $changedImportantFields = array_intersect(
            array_keys($user->getChanges()),
            $importantFields
        );

        if (!empty($changedImportantFields)) {
            $original = $user->getOriginal();
            $changes = $user->getChanges();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'event' => 'user_updated',
                'description' => "User '{$user->full_name}' ({$user->username}) was updated.",
                'properties' => [
                    'changed_fields' => $changedImportantFields,
                    'old_values' => array_intersect_key($original, array_flip($changedImportantFields)),
                    'new_values' => array_intersect_key($changes, array_flip($changedImportantFields)),
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        }
    }

    public function deleted(User $user): void
    {
        // Revoke all tokens when user is deleted
        $user->tokens()->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'event' => 'user_deleted',
            'description' => "User '{$user->full_name}' ({$user->username}) was deleted.",
            'properties' => [
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
