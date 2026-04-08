<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSyncController extends Controller
{
    /**
     * Receives user data from the transmittal system.
     */
    public function receive(Request $request)
    {
        $incomingToken = $request->header('X-SYNC-TOKEN');
        if ($incomingToken !== env('INTERNAL_API_TOKEN')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'email_hash' => 'nullable|string',
            'first_name' => 'required|string',
            'lastname' => 'required|string',
            'middle_name' => 'nullable|string',
            'suffix' => 'nullable|string',
            'role' => 'nullable|string',
            'assigned' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'email_verified_at' => 'nullable|string',
            'profile_image' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        try {
            $hashedEmail = $validated['email_hash'] ?? hash_hmac('sha256', strtolower($validated['email']), config('app.key'));

            // We use a global state to prevent infinite loops during bi-directional sync
            config(['syncing_from_external' => true]);

            $user = User::where('email_searchable', $hashedEmail)->first();

            $userData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['lastname'],
                'middle_name' => $validated['middle_name'] ?? null,
                'suffix' => $validated['suffix'] ?? null,
                'email' => $validated['email'],
                'email_searchable' => $hashedEmail,
                'role' => $validated['role'] ?? 'admin',
                'assign' => $validated['assigned'] ?? 'National',
                'is_active' => $validated['is_active'] ?? true,
                'email_verified_at' => $validated['email_verified_at'] ?? null,
                'avatar' => $validated['profile_image'] ?? null,
            ];

            if (isset($validated['password'])) {
                // If the password looks like a bcrypt hash, don't re-hash it
                $userData['password'] = (str_starts_with($validated['password'], '$2y$') || str_starts_with($validated['password'], '$2a$'))
                    ? $validated['password']
                    : Hash::make($validated['password']);
            }

            if (!$user) {
                if (!isset($userData['password'])) {
                    $userData['password'] = Hash::make('deped-password-2024');
                }
                $user = User::create($userData);
                $status = 'created';
            } else {
                $user->update($userData);
                $status = 'updated';
            }

            return response()->json([
                'success' => true,
                'message' => "User {$status} successfully via sync.",
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error("User Sync Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
