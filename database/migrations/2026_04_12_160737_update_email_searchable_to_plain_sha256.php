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
        DB::table('users')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                if ($user->email) {
                    try {
                        // Decrypt current email if encrypted
                        $email = Crypt::decryptString($user->email);
                    } catch (\Exception $e) {
                        $email = $user->email;
                    }
                    
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'email_searchable' => \App\Models\User::generateEmailHash($email)
                        ]);
                }
            }
        });
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
