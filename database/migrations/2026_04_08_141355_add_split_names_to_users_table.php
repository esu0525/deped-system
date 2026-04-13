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
        if (!Schema::hasColumn('users', 'first_name') || 
            !Schema::hasColumn('users', 'middle_name') || 
            !Schema::hasColumn('users', 'last_name') || 
            !Schema::hasColumn('users', 'email_searchable')) {
            
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'first_name')) {
                    $table->string('first_name')->after('id')->nullable();
                }
                if (!Schema::hasColumn('users', 'middle_name')) {
                    $table->string('middle_name')->after('first_name')->nullable();
                }
                if (!Schema::hasColumn('users', 'last_name')) {
                    $table->string('last_name')->after('middle_name')->nullable();
                }
                if (!Schema::hasColumn('users', 'email_searchable')) {
                    $table->string('email_searchable')->after('email')->nullable()->unique();
                }
            });
        }

        // Migrate data
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $updates = [];
            
            if (isset($user->name)) {
                $parts = explode(' ', $user->name);
                $last = array_pop($parts);
                $first = array_shift($parts);
                $middle = implode(' ', $parts);
                
                $updates['first_name'] = $first;
                $updates['middle_name'] = $middle;
                $updates['last_name'] = $last;
            }

            if (isset($user->email)) {
                try {
                    $plainEmail = Crypt::decryptString($user->email);
                } catch (\Exception $e) {
                    $plainEmail = $user->email;
                }
                $updates['email_searchable'] = hash_hmac('sha256', strtolower($plainEmail), config('app.key'));
            }

            if (!empty($updates)) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
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
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? ''));
            DB::table('users')->where('id', $user->id)->update([
                'name' => $fullName,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            foreach (['first_name', 'middle_name', 'last_name', 'email_searchable'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
