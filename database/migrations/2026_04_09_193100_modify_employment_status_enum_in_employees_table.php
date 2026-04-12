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
        // 1. Temporarily change to VARCHAR to allow any value and make it nullable
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status VARCHAR(255) NULL");

        // 2. Clear or map existing data
        // We set everything that doesn't match 'Regular' or 'Contractual' to NULL
        // since the user wants the status to be manually selected.
        DB::table('employees')
            ->whereNotIn('employment_status', ['Regular', 'Contractual'])
            ->update(['employment_status' => null]);

        // 3. Now safely apply the new ENUM restriction
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status ENUM('Regular', 'Contractual') NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status ENUM('Permanent', 'Temporary', 'Casual', 'Contractual', 'Job Order') DEFAULT 'Permanent'");
    }
};
