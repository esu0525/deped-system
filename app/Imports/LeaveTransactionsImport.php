<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\LeaveType;
use App\Models\LeaveTransaction;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class LeaveTransactionsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected $rows = 0;
    protected $successRows = 0;

    public function model(array $row)
    {
        $this->rows++;

        $employee = Employee::where('employee_id', $row['employee_id'])->first();
        if (!$employee)
            return null;

        $year = isset($row['year']) ? $row['year'] : now()->year;

        $leaveCard = LeaveCard::where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        if (!$leaveCard)
            return null;

        $leaveType = null;
        if (isset($row['leave_type'])) {
            $leaveType = LeaveType::where('code', $row['leave_type'])
                ->orWhere('name', $row['leave_type'])
                ->first();
        }

        $transaction = LeaveTransaction::create([
            'employee_id' => $employee->id,
            'leave_card_id' => $leaveCard->id,
            'leave_type_id' => $leaveType?->id,
            'transaction_date' => isset($row['transaction_date'])
                ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['transaction_date'])
                : now(),
            'transaction_type' => $row['transaction_type'] ?? 'Deduction',
            'days' => $row['days'] ?? 0,
            'remarks' => $row['remarks'] ?? null,
            'encoded_by' => auth()->id(),
        ]);

        if ($transaction) {
            $leaveCard->recalculate();
            $this->successRows++;
        }

        return $transaction;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required',
            'days' => 'required|numeric',
        ];
    }

    public function getTotalRows()
    {
        return $this->rows;
    }
    public function getSuccessRows()
    {
        return $this->successRows;
    }
    public function getFailedRows()
    {
        return $this->failures()->count();
    }
    public function getErrors()
    {
        return $this->failures();
    }
}
