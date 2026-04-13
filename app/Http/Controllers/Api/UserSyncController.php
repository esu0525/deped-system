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
        $token = env('INTERNAL_API_TOKEN');
        $providedToken = $request->bearerToken();
        
        if ($providedToken !== $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // --- DEBUG LOG ---
        \Illuminate\Support\Facades\Log::info('DepEd-System Incoming Sync Data:', $request->all());

        if ($request->action === 'deleted') {
            $user = User::find($request->id);
            if (!$user && $request->email_hash) {
                $user = User::where('email_searchable', $request->email_hash)->first();
            }
            if ($user) {
                $user->delete();
                return response()->json(['success' => true, 'message' => 'User deleted successfully via sync.']);
            }
            return response()->json(['success' => false, 'message' => 'User not found for deletion.'], 404);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'first_name' => 'required|string',
            'lastname' => 'required|string',
        ]);

        try {
            $hashedEmail = $request->email_hash ?? User::generateEmailHash($request->email);

            // We use a global state or static flag to prevent infinite loops
            \App\Services\SyncService::$isSyncing = true;

            $user = null;
            if ($request->has('id')) {
                $user = User::find($request->id);
            }
            
            if (!$user) {
                $user = User::where('email_searchable', $hashedEmail)->first();
            }

            // Determine role mapping
            $incomingRole = $request->role;
            $mappedRole = $incomingRole;
            if ($incomingRole === 'admin') {
                $mappedRole = 'system_admin';
            } elseif ($incomingRole === 'user') {
                $mappedRole = 'coordinator'; // default mapping for 'user'
            }

            // Handle Profile Image Sync
            $avatarPath = $request->profile_image;
            if ($request->filled('profile_image_base64')) {
                $base64String = $request->profile_image_base64;
                
                // Remove base64 prefix if present (e.g., data:image/jpeg;base64,)
                if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                    $base64String = substr($base64String, strpos($base64String, ',') + 1);
                }
                
                $imageData = base64_decode($base64String);
                if ($imageData) {
                    // Ensure the relative path is used. If not provided, generate one.
                    $fileName = $request->profile_image ?: ('profile-images/' . uniqid() . '.jpg');
                    
                    // Make sure the filename is clean
                    $fileName = ltrim($fileName, '/');
                    
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageData);
                    $avatarPath = $fileName;
                    \Illuminate\Support\Facades\Log::info("DepEd-System: Profile image synced: {$fileName}");
                }
            }

            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->lastname,
                'middle_name' => $request->middle_name,
                'suffix' => $request->suffix,
                'email' => $request->email,
                'email_searchable' => $hashedEmail,
                'role' => $mappedRole ?? 'coordinator',
                'assign' => $request->assigned ?? 'National',
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
                'email_verified_at' => $request->email_verified_at,
                'avatar' => $avatarPath,
            ];

            if ($request->filled('password')) {
                $userData['password'] = $request->password;
            }

            if (!$user) {
                if ($request->has('id')) {
                    $userData['id'] = $request->id;
                }
                if (!isset($userData['password'])) {
                    $userData['password'] = Hash::make('deped-password-2024');
                }
                $user = User::create($userData);
                $status = 'created';
            } else {
                $user->update($userData);
                $status = 'updated';
            }

            \App\Services\SyncService::$isSyncing = false;

            return response()->json([
                'success' => true,
                'message' => "User {$status} successfully in DepEd System via sync.",
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            \App\Services\SyncService::$isSyncing = false;
            Log::error("User Sync Error in DepEd System: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
