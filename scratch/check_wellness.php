<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\LeaveTransaction;
use App\Models\LeaveApplication;

// Use the employee ID from the screenshot (1140)
$employeeId = 1140; 
$employee = Employee::find($employeeId);
$year = 2026;

$wellnessEarned = (float)LeaveTransaction::where('employee_id', $employee->id)
    ->where('transaction_type', 'earned')
    ->whereYear('transaction_date', $year)
    ->whereHas('leaveType', function ($q) {
        $q->where('name', 'like', '%Wellness%');
    })
    ->sum('days');

$wellnessUsed = (float)LeaveApplication::where('employee_id', $employee->id)
    ->where('status', 'Approved')
    ->whereYear('date_filed', $year)
    ->whereHas('leaveType', function ($q) {
        $q->where('name', 'like', '%Wellness%');
    })
    ->sum('num_days');

echo "Wellness Earned: $wellnessEarned\n";
echo "Wellness Used: $wellnessUsed\n";
echo "Wellness Balance: " . max(0, $wellnessEarned - $wellnessUsed) . "\n";
