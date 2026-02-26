<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('full_name');
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('position')->nullable();
            $table->enum('employment_status', ['Permanent', 'Temporary', 'Casual', 'Contractual', 'Job Order'])->default('Permanent');
            $table->date('date_hired')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_picture')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Resigned', 'Retired'])->default('Active');
            $table->timestamps();
        });

        // Leave Types
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_earnable')->default(false);
            $table->decimal('monthly_credit', 5, 2)->default(0);
            $table->timestamps();
        });

        // Leave Cards (per employee, per year)
        Schema::create('leave_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->decimal('vl_beginning_balance', 8, 3)->default(0);
            $table->decimal('sl_beginning_balance', 8, 3)->default(0);
            $table->decimal('vl_earned', 8, 3)->default(0);
            $table->decimal('sl_earned', 8, 3)->default(0);
            $table->decimal('vl_used', 8, 3)->default(0);
            $table->decimal('sl_used', 8, 3)->default(0);
            $table->decimal('vl_balance', 8, 3)->default(0);
            $table->decimal('sl_balance', 8, 3)->default(0);
            $table->decimal('forced_leave_balance', 8, 3)->default(0);
            $table->decimal('special_leave_balance', 8, 3)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'year']);
        });

        // Leave Transactions (ledger)
        Schema::create('leave_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->date('transaction_date');
            $table->enum('transaction_type', ['earned', 'used', 'adjustment', 'beginning_balance']);
            $table->decimal('days', 8, 3)->default(0);
            $table->decimal('vl_balance_after', 8, 3)->default(0);
            $table->decimal('sl_balance_after', 8, 3)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Leave Applications
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('num_days', 8, 3)->default(0);
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_transactions');
        Schema::dropIfExists('leave_cards');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
    }
};
