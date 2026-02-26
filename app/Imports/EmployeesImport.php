<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected $rows = 0;
    protected $successRows = 0;

    public function model(array $row)
    {
        $this->rows++;

        $employee = Employee::updateOrCreate(
        ['employee_id' => $row['employee_id']],
        [
            'full_name' => $row['full_name'],
            'gender' => $row['gender'] ?? null,
            'position' => $row['position'] ?? null,
            'employment_status' => $row['employment_status'] ?? 'Permanent',
            'date_hired' => isset($row['date_hired']) ?\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_hired']) : null,
            'email' => $row['email'] ?? null,
            'contact_number' => $row['contact_number'] ?? null,
            'address' => $row['address'] ?? null,
            'status' => 'Active',
        ]
        );

        if ($employee) {
            $this->successRows++;
            // Auto create leave card if not exists
            app(\App\Services\LeaveCardService::class)->getOrCreateLeaveCard($employee);
        }

        return $employee;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required',
            'full_name' => 'required',
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
