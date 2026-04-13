<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id')->nullable();
            $table->string('middle_name')->after('first_name')->nullable();
            $table->string('last_name')->after('middle_name')->nullable();
            $table->string('email_searchable')->after('email')->nullable()->unique();
            $table->text('email')->change();
            $table->text('access')->nullable()->change();
        });

        // Migrate and Encrypt existing data
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $parts = explode(' ', $user->name);
            $first = '';
            $middle = '';
            $last = '';

            if (str_contains($user->name, ',')) {
                $commaParts = explode(',', $user->name);
                $last = trim($commaParts[0]);
                $remaining = trim($commaParts[1]);
                $remParts = explode(' ', $remaining);
                $first = $remParts[0];
                $middle = count($remParts) > 1 ? implode(' ', array_slice($remParts, 1)) : '';
            } else {
                $last = array_pop($parts);
                $first = array_shift($parts);
                $middle = implode(' ', $parts);
            }

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'email_searchable' => hash_hmac('sha256', strtolower($user->email), config('app.key')),
                'email' => Crypt::encryptString($user->email),
                'access' => $user->access ? Crypt::encryptString($user->access) : null,
            ]);
        }
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->nullable();
        });

        $users = DB::table('users')->get();
        foreach ($users as $user) {
            try {
                $email = Crypt::decryptString($user->email);
                $access = $user->access ? Crypt::decryptString($user->access) : null;
            } catch (\Exception $e) {
                $email = $user->email;
                $access = $user->access;
            }

            DB::table('users')->where('id', $user->id)->update([
                'name' => trim("{$user->first_name} {$user->middle_name} {$user->last_name}"),
                'email' => $email,
                'access' => $access,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'email_searchable']);
        });
    }
};
