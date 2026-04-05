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
        Schema::table('leave_application_details', function (Blueprint $table) {
            $table->decimal('cto_earned_days', 12, 6)->nullable()->after('num_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_application_details', function (Blueprint $table) {
            $table->dropColumn('cto_earned_days');
        });
    }
};
