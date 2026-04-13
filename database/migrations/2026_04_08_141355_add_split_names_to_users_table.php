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
                $table->string('email_searchable')->after('email')->nullable();
            }
        });

        // Migrate data in chunks
        DB::table('users')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $updates = [];
                
                // Only split if name exists and new fields are empty
                if (isset($user->name) && !empty($user->name) && empty($user->first_name)) {
                    $parts = explode(' ', trim($user->name));
                    if (count($parts) > 0) {
                        $last = array_pop($parts);
                        $first = array_shift($parts);
                        $middle = implode(' ', $parts);
                        
                        $updates['first_name'] = $first ?: $last;
                        $updates['middle_name'] = $middle;
                        $updates['last_name'] = $first ? $last : '';
                    }
                }

                if (isset($user->email) && empty($user->email_searchable)) {
                    try {
                        $plainEmail = Crypt::decryptString($user->email);
                    } catch (\Exception $e) {
                        $plainEmail = $user->email;
                    }
                    $updates['email_searchable'] = hash_hmac('sha256', strtolower(trim($plainEmail)), config('app.key'));
                }

                if (!empty($updates)) {
                    DB::table('users')->where('id', $user->id)->update($updates);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
            
            // Add unique constraint separately to avoid failures if duplicates exist (though they shouldn't)
            // But if we are in fresh, it's fine.
            // Check if index exists before adding it
            $conn = Schema::getConnection();
            $dbName = $conn->getDatabaseName();
            $indexes = $conn->select("SHOW INDEX FROM users WHERE Key_name = 'users_email_searchable_unique'");
            
            if (empty($indexes) && Schema::hasColumn('users', 'email_searchable')) {
                $table->unique('email_searchable');
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
