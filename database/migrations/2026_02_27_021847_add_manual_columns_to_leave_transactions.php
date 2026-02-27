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
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->decimal('vl_wop', 8, 3)->nullable()->after('vl_balance_after');
            $table->decimal('sl_wop', 8, 3)->nullable()->after('sl_balance_after');
            $table->string('action_taken')->nullable()->after('sl_wop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropColumn(['vl_wop', 'sl_wop', 'action_taken']);
        });
    }
};
