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
            $table->unsignedBigInteger('leave_application_id')->nullable()->after('leave_card_id');
            $table->foreign('leave_application_id')->references('id')->on('leave_applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropForeign(['leave_application_id']);
            $table->dropColumn('leave_application_id');
        });
    }
};
