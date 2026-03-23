<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->string('cto_title', 255)->nullable()->after('period');
        });

        // Backfill cto_title from period for existing CTO transactions
        $transactions = DB::table('leave_transactions')
            ->where('period', 'LIKE', '%CTO:%')
            ->get();

        foreach ($transactions as $t) {
            $title = '';
            // Match "ADD: CTO: Title" or "LESS: CTO: Title"
            if (preg_match('/CTO:\s*(.*)/i', $t->period, $matches)) {
                $title = trim($matches[1]);
            }
            if ($title) {
                DB::table('leave_transactions')
                    ->where('id', $t->id)
                    ->update(['cto_title' => $title]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropColumn('cto_title');
        });
    }
};
