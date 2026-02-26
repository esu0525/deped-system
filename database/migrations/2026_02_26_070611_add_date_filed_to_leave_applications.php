<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->date('date_filed')->nullable()->after('employee_id');
        });

        // Set existing records date_filed to created_at date
        DB::table('leave_applications')->whereNull('date_filed')->update([
            'date_filed' => DB::raw('DATE(created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn('date_filed');
        });
    }
};
