<?php

namespace App\Observers;

use App\Models\User;
use App\Services\SyncService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        SyncService::syncUser($user, 'created');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        SyncService::syncUser($user, 'updated');
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        SyncService::syncUser($user, 'deleted');
    }
}
