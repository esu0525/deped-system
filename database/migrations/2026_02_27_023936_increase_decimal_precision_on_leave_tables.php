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
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->decimal('vl_beginning_balance', 15, 8)->default(0)->change();
            $table->decimal('sl_beginning_balance', 15, 8)->default(0)->change();
            $table->decimal('vl_earned', 15, 8)->default(0)->change();
            $table->decimal('sl_earned', 15, 8)->default(0)->change();
            $table->decimal('vl_used', 15, 8)->default(0)->change();
            $table->decimal('sl_used', 15, 8)->default(0)->change();
            $table->decimal('vl_balance', 15, 8)->default(0)->change();
            $table->decimal('sl_balance', 15, 8)->default(0)->change();
            $table->decimal('forced_leave_balance', 15, 8)->default(0)->change();
            $table->decimal('special_leave_balance', 15, 8)->default(0)->change();
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->decimal('days', 15, 8)->default(0)->change();
            $table->decimal('vl_balance_after', 15, 8)->default(0)->change();
            $table->decimal('sl_balance_after', 15, 8)->default(0)->change();
            $table->decimal('vl_earned', 15, 8)->nullable()->change();
            $table->decimal('vl_used', 15, 8)->nullable()->change();
            $table->decimal('sl_earned', 15, 8)->nullable()->change();
            $table->decimal('sl_used', 15, 8)->nullable()->change();
            $table->decimal('vl_wop', 15, 8)->nullable()->change();
            $table->decimal('sl_wop', 15, 8)->nullable()->change();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->decimal('num_days', 15, 8)->default(0)->change();
            $table->decimal('cert_vl_total_earned', 15, 8)->nullable()->change();
            $table->decimal('cert_vl_less_this', 15, 8)->nullable()->change();
            $table->decimal('cert_vl_balance', 15, 8)->nullable()->change();
            $table->decimal('cert_sl_total_earned', 15, 8)->nullable()->change();
            $table->decimal('cert_sl_less_this', 15, 8)->nullable()->change();
            $table->decimal('cert_sl_balance', 15, 8)->nullable()->change();
        });

        Schema::table('leave_application_details', function (Blueprint $table) {
            $table->decimal('num_days', 15, 8)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->decimal('vl_beginning_balance', 8, 3)->default(0)->change();
            $table->decimal('sl_beginning_balance', 8, 3)->default(0)->change();
            $table->decimal('vl_earned', 8, 3)->default(0)->change();
            $table->decimal('sl_earned', 8, 3)->default(0)->change();
            $table->decimal('vl_used', 8, 3)->default(0)->change();
            $table->decimal('sl_used', 8, 3)->default(0)->change();
            $table->decimal('vl_balance', 8, 3)->default(0)->change();
            $table->decimal('sl_balance', 8, 3)->default(0)->change();
            $table->decimal('forced_leave_balance', 8, 3)->default(0)->change();
            $table->decimal('special_leave_balance', 8, 3)->default(0)->change();
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->decimal('days', 8, 3)->default(0)->change();
            $table->decimal('vl_balance_after', 8, 3)->default(0)->change();
            $table->decimal('sl_balance_after', 8, 3)->default(0)->change();
            $table->decimal('vl_earned', 8, 3)->nullable()->change();
            $table->decimal('vl_used', 8, 3)->nullable()->change();
            $table->decimal('sl_earned', 8, 3)->nullable()->change();
            $table->decimal('sl_used', 8, 3)->nullable()->change();
            $table->decimal('vl_wop', 8, 3)->nullable()->change();
            $table->decimal('sl_wop', 8, 3)->nullable()->change();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->decimal('num_days', 8, 3)->default(0)->change();
            $table->decimal('cert_vl_total_earned', 8, 3)->nullable()->change();
            $table->decimal('cert_vl_less_this', 8, 3)->nullable()->change();
            $table->decimal('cert_vl_balance', 8, 3)->nullable()->change();
            $table->decimal('cert_sl_total_earned', 8, 3)->nullable()->change();
            $table->decimal('cert_sl_less_this', 8, 3)->nullable()->change();
            $table->decimal('cert_sl_balance', 8, 3)->nullable()->change();
        });

        Schema::table('leave_application_details', function (Blueprint $table) {
            $table->decimal('num_days', 8, 3)->default(0)->change();
        });
    }
};
