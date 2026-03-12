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
            $table->decimal('vl_balance_after', 15, 8)->nullable()->change();
            $table->decimal('sl_balance_after', 15, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->decimal('vl_balance_after', 15, 8)->nullable(false)->default(0)->change();
            $table->decimal('sl_balance_after', 15, 8)->nullable(false)->default(0)->change();
        });
    }
};
