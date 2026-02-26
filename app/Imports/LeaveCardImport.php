<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\LeaveCard;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class LeaveCardImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
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

        $leaveCard = LeaveCard::updateOrCreate(
        ['employee_id' => $employee->id, 'year' => $year],
        [
            'vl_beginning_balance' => $row['vl_balance'] ?? 0,
            'sl_beginning_balance' => $row['sl_balance'] ?? 0,
            'forced_leave_balance' => $row['forced_leave_balance'] ?? 0,
            'special_leave_balance' => $row['special_leave_balance'] ?? 0,
        ]
        );

        if ($leaveCard) {
            $leaveCard->recalculate();
            $this->successRows++;
        }

        return $leaveCard;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required',
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
