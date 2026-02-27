<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // 7.A Certification of Leave Credits (saved on approval)
            if (!Schema::hasColumn('leave_applications', 'cert_vl_total_earned')) {
                $table->decimal('cert_vl_total_earned', 8, 3)->nullable()->after('commutation');
            }
            if (!Schema::hasColumn('leave_applications', 'cert_vl_less_this')) {
                $table->decimal('cert_vl_less_this', 8, 3)->nullable()->after('cert_vl_total_earned');
            }
            if (!Schema::hasColumn('leave_applications', 'cert_vl_balance')) {
                $table->decimal('cert_vl_balance', 8, 3)->nullable()->after('cert_vl_less_this');
            }
            if (!Schema::hasColumn('leave_applications', 'cert_sl_total_earned')) {
                $table->decimal('cert_sl_total_earned', 8, 3)->nullable()->after('cert_vl_balance');
            }
            if (!Schema::hasColumn('leave_applications', 'cert_sl_less_this')) {
                $table->decimal('cert_sl_less_this', 8, 3)->nullable()->after('cert_sl_total_earned');
            }
            if (!Schema::hasColumn('leave_applications', 'cert_sl_balance')) {
                $table->decimal('cert_sl_balance', 8, 3)->nullable()->after('cert_sl_less_this');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn([
                'cert_vl_total_earned', 'cert_vl_less_this', 'cert_vl_balance',
                'cert_sl_total_earned', 'cert_sl_less_this', 'cert_sl_balance',
            ]);
        });
    }
};
