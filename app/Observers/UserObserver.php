<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "saved" event (created or updated).
     */
    public function saved(User $user)
    {
        // Prevent infinite loop if the change came from the sync endpoint
        if (config('syncing_from_external')) {
            return;
        }

        // Only sync if relevant profile data changed (not OTP, login timestamps, etc.)
        $syncFields = [
            'first_name', 'middle_name', 'last_name', 'suffix', 'email', 
            'email_searchable', 'avatar', 'password', 'role', 'access', 'assign', 'is_active'
        ];
        
        $changes = array_keys($user->getChanges());
        // If it's a new record or a relevant change, proceed. 
        // If it only changed OTP/login fields, skip sync.
        if (!$user->wasRecentlyCreated && !empty($changes) && empty(array_intersect($changes, $syncFields))) {
            return;
        }

        // Only sync admin, coordinator, and ojt to the Transmittal System
        $syncableRoles = ['admin', 'coordinator', 'ojt'];
        if (!in_array($user->role, $syncableRoles)) {
            return;
        }

        $url = env('EXTERNAL_SYNC_URL');
        $token = env('EXTERNAL_SYNC_TOKEN');

        if (!$url || !$token) {
            return;
        }

        try {
            // Added 5s timeout to prevent blocking if external system is slow
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'X-SYNC-SOURCE' => 'DEPED-SYSTEM'
            ])->post($url, [
                'id' => $user->id,
                'lastname' => $user->last_name,
                'middle_name' => $user->middle_name,
                'first_name' => $user->first_name,
                'suffix' => $user->suffix,
                'email' => $user->email,
                'email_hash' => $user->email_searchable,
                'profile_image' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'password' => $user->password,
                'role' => $user->role,
                'assigned' => $user->assign, 
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
            ]);

            if ($response->failed()) {
                Log::warning("Transmittal Sync Failed for user {$user->email}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Transmittal Sync Exception: " . $e->getMessage());
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user)
    {
        if (config('syncing_from_external')) {
            return;
        }

        // Only sync admin, coordinator, and ojt to the Transmittal System
        $syncableRoles = ['admin', 'coordinator', 'ojt'];
        if (!in_array($user->role, $syncableRoles)) {
            return;
        }

        $url = env('TRANSMITTAL_SYNC_URL') . '/delete'; // Assuming they have a delete endpoint or handle it
        $token = env('TRANSMITTAL_SYNC_TOKEN');

        if (!$url || !$token) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-SYNC-SOURCE' => 'DEPED-SYSTEM'
            ])->post($url, ['email' => $user->email]);
        } catch (\Exception $e) {
            Log::error("Transmittal Delete Sync Error: " . $e->getMessage());
        }
    }
}
