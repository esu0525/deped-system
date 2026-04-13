<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncService
{
    /**
     * Prevents infinite loops during synchronization.
     */
    public static $isSyncing = false;

    /**
     * Sync user data to an external system.
     *
     * @param \App\Models\User $user
     * @param string $action 'created', 'updated', or 'deleted'
     * @return void
     */
    public static function syncUser($user, $action = 'updated')
    {
        // Check if we are currently syncing to prevent infinite loops
        if (self::$isSyncing || config('syncing_from_external')) {
            return;
        }

        $url = env('USER_SYNC_URL', env('EXTERNAL_SYNC_URL'));
        $token = env('USER_SYNC_TOKEN', env('EXTERNAL_SYNC_TOKEN'));

        if (!$url) {
            return;
        }

        try {
            $avatarBase64 = null;
            if ($user->avatar) {
                $path = storage_path('app/public/' . $user->avatar);
                if (file_exists($path)) {
                    $avatarBase64 = base64_encode(file_get_contents($path));
                }
            }

            $data = [
                'id' => $user->id,
                'lastname' => $user->last_name,
                'middle_name' => $user->middle_name,
                'first_name' => $user->first_name,
                'suffix' => $user->suffix,
                'email' => $user->email,
                'email_hash' => $user->email_searchable,
                'profile_image' => $user->avatar,
                'profile_image_base64' => $avatarBase64,
                'password' => $user->password,
                'role' => $user->role,
                'assigned' => $user->assign,
                'is_active' => $user->is_active ? 1 : 0,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
                'action' => $action,
            ];
            
            Log::info('SyncService: Sending payload for User ID: ' . $user->id . ' has_image: ' . ($avatarBase64 ? 'Yes' : 'No'));
            Log::debug('SyncService payload:', $data);

            // If deleted, we might only need the ID and email_hash for identification
            if ($action === 'deleted') {
                $data = [
                    'id' => $user->id,
                    'email_hash' => $user->email_searchable,
                    'action' => 'deleted'
                ];
            }

            $response = Http::withHeaders([
                'X-Sync-Source' => 'deped-system',
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post($url, $data);

            if (!$response->successful()) {
                Log::error('External User Sync Failed from deped-system to ' . $url . '. Status: ' . $response->status() . ' Body: ' . $response->body());
            } else {
                Log::info('External User Sync Success for User ID: ' . $user->id . ' Action: ' . $action);
            }

        } catch (\Exception $e) {
            Log::error('External User Sync Connection Error in deped-system: ' . $e->getMessage());
        }
    }

    public static function syncLeaveApplication($application)
    {
        $apiUrl = env('RECORD_SYNC_URL');
        $token = env('RECORD_SYNC_TOKEN');

        if (!$apiUrl) {
            // Fallback to old behavior if new Env vars not set
            $baseUrl = env('EXTERNAL_SYNC_URL');
            $token = env('EXTERNAL_SYNC_TOKEN');
            if (!$baseUrl) return;
            $apiUrl = dirname($baseUrl) . '/leave-records';
        }

        $application->load(['employee.department', 'encoder', 'details.leaveType']);

        foreach ($application->details as $detail) {
            try {
                $data = [
                    'name'              => $application->employee->full_name,
                    'position'          => $application->employee->position,
                    'school'            => $application->employee->department->name ?? 'N/A',
                    'type_of_leave'     => $detail->leaveType->code ?? $detail->leaveType->name ?? 'N/A',
                    'inclusive_dates'   => $detail->inclusive_dates,
                    'remarks'           => $detail->is_with_pay ? 'With Pay' : 'Without Pay',
                    'date_of_action'    => $application->date_filed->format('Y-m-d'),
                    'deduction_remarks' => null, // Always empty as requested
                    'incharge'          => $application->encoder->first_name ?? 'System',
                    'encoder_email'     => $application->encoder->email ?? null,
                    'encoder_id'        => $application->encoder->id ?? null,
                    'assigned'          => $application->encoder->assign ?? $application->employee->category ?? 'national',
                ];

                Log::info('SyncService: Sending Leave Record for Application ID: ' . $application->id);

                $response = Http::withHeaders([
                    'X-Sync-Source' => 'deped-system',
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->post($apiUrl, $data);

                if (!$response->successful()) {
                    Log::error('Leave Record Sync Failed to ' . $apiUrl . '. Status: ' . $response->status() . ' Body: ' . $response->body());
                } else {
                    Log::info('Leave Record Sync Success for Application Detail ID: ' . $detail->id);
                }

            } catch (\Exception $e) {
                Log::error('Leave Record Sync Connection Error: ' . $e->getMessage());
            }
        }
    }
}

