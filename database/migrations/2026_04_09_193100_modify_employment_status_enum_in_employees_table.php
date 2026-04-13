<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temporarily change to string to allow any value and make it nullable
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employment_status')->nullable()->change();
        });

        // 2. Clear or map existing data
        DB::table('employees')
            ->whereNotIn('employment_status', ['Regular', 'Contractual'])
            ->update(['employment_status' => null]);

        // 3. Now apply the new ENUM restriction (or string if preferred for SQLite)
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('employment_status', ['Regular', 'Contractual'])->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('employment_status', ['Permanent', 'Temporary', 'Casual', 'Contractual', 'Job Order'])
                ->default('Permanent')
                ->change();
        });
    }
};
