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
            $table->string('vl_wop_reason')->nullable()->after('vl_wop');
            $table->string('sl_wop_reason')->nullable()->after('sl_wop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropColumn(['vl_wop_reason', 'sl_wop_reason']);
        });
    }
};
