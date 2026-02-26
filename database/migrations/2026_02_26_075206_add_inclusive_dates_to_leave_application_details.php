<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('leave_application_details', function (Blueprint $table) {
            $table->string('inclusive_dates')->nullable()->after('leave_type_id');
            $table->string('other_type')->nullable()->after('inclusive_dates');
        });
    }

    public function down(): void
    {
        Schema::table('leave_application_details', function (Blueprint $table) {
            $table->dropColumn(['inclusive_dates', 'other_type']);
        });
    }
};
