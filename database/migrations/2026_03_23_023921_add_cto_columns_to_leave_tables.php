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
            $table->decimal('cto_beginning_balance', 12, 6)->default(0)->after('sl_balance');
            $table->decimal('cto_balance', 12, 6)->default(0)->after('cto_beginning_balance');
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->decimal('cto_earned', 12, 6)->nullable()->after('sl_wop_reason');
            $table->decimal('cto_used', 12, 6)->nullable()->after('cto_earned');
            $table->decimal('cto_balance_after', 12, 6)->nullable()->after('cto_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->dropColumn(['cto_beginning_balance', 'cto_balance']);
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropColumn(['cto_earned', 'cto_used', 'cto_balance_after']);
        });
    }
};
