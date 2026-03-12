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
        Schema::table('leave_applications', function (Blueprint $table) {
            // 6.B Details of Leave
            if (!Schema::hasColumn('leave_applications', 'leave_location')) {
                $table->string('leave_location')->nullable()->after('remarks');
                $table->text('leave_location_detail')->nullable()->after('leave_location');
                $table->string('sick_leave_type')->nullable()->after('leave_location_detail');
                $table->text('sick_leave_detail')->nullable()->after('sick_leave_type');
                $table->text('women_leave_detail')->nullable()->after('sick_leave_detail');
                $table->string('study_leave_type')->nullable()->after('women_leave_detail');
                $table->text('other_leave_detail')->nullable()->after('study_leave_type');
            }
            
            // 6.D Commutation
            if (!Schema::hasColumn('leave_applications', 'commutation')) {
                $table->string('commutation')->nullable()->after('other_leave_detail');
                $table->string('salary')->nullable()->after('commutation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn([
                'leave_location', 'leave_location_detail', 
                'sick_leave_type', 'sick_leave_detail', 
                'women_leave_detail', 'study_leave_type', 
                'other_leave_detail', 'commutation', 'salary'
            ]);
        });
    }
};
