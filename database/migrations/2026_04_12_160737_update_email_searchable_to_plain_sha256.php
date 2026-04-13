<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            try {
                // Accessing $user->email will trigger decryption. 
                // We wrap it in try-catch to handle "The MAC is invalid" errors.
                if ($user->email) {
                    $user->email_searchable = \App\Models\User::generateEmailHash($user->email);
                    $user->save();
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Skip users that cannot be decrypted (invalid APP_KEY or corrupted data)
                continue;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            if ($user->email) {
                // Revert to legacy HMAC hash
                $user->email_searchable = hash_hmac('sha256', strtolower(trim($user->email)), config('app.key'));
                $user->save();
            }
        }
    }
};
