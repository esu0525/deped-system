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
            $table->decimal('vl_earned', 8, 3)->nullable()->after('days');
            $table->decimal('vl_used', 8, 3)->nullable()->after('vl_earned');
            $table->decimal('sl_earned', 8, 3)->nullable()->after('vl_used');
            $table->decimal('sl_used', 8, 3)->nullable()->after('sl_earned');
            $table->string('transaction_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropColumn(['vl_earned', 'vl_used', 'sl_earned', 'sl_used']);
            $table->string('transaction_type')->nullable(false)->change();
        });
    }
};
