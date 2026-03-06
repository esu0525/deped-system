<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
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

        // Support both formats:
        // Format A (template): employee_id, full_name, ..., position, ...
        // Format B (user file): school, name, position

        $fullName = $row['full_name'] ?? $row['name'] ?? $row['full name'] ?? null;
        $employeeId = $row['employee_id'] ?? $row['employee id'] ?? $row['id'] ?? $row['reference_id'] ?? null;
        $position = $row['position'] ?? null;
        $school = $row['school'] ?? $row['department'] ?? $row['office'] ?? null;
        $contact = $row['contact_number'] ?? $row['contact'] ?? $row['phone'] ?? null;
        $address = $row['address'] ?? null;
        $status = $row['employment_status'] ?? $row['status'] ?? 'Permanent';
        $dateHired = $row['date_hired'] ?? $row['date hired'] ?? null;

        if (!$fullName) {
            return null;
        }

        // Auto-generate employee_id if not provided
        if (!$employeeId) {
            $employeeId = self::generateUniqueId();
        }

        // Find or create department/school if provided
        $departmentId = null;
        if ($school) {
            $schoolName = trim($school);
            $department = Department::where('name', $schoolName)->first();
            if (!$department) {
                // Generate a unique code from the school name
                $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $schoolName), 0, 6));
                $code = $baseCode;
                $counter = 1;
                while (Department::where('code', $code)->exists()) {
                    $code = $baseCode . $counter;
                    $counter++;
                }
                $department = Department::create([
                    'name' => $schoolName,
                    'code' => $code,
                ]);
            }
            $departmentId = $department->id;
        }

        // Parse date_hired if it's an Excel serial number
        $parsedDate = null;
        if ($dateHired) {
            try {
                if (is_numeric($dateHired)) {
                    $parsedDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateHired);
                } else {
                    $parsedDate = \Carbon\Carbon::parse($dateHired);
                }
            } catch (\Exception $e) {
                $parsedDate = null;
            }
        }

        $employee = Employee::updateOrCreate(
            ['employee_id' => $employeeId],
            [
                'full_name' => trim($fullName),
                'department_id' => $departmentId,
                'position' => $position ? trim($position) : null,
                'employment_status' => $status,
                'date_hired' => $parsedDate,
                'contact_number' => $contact,
                'address' => $address,
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

    /**
     * Generate a unique 6-digit employee ID
     */
    private static function generateUniqueId(): string
    {
        do {
            $id = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (Employee::where('employee_id', $id)->exists());

        return $id;
    }

    public function rules(): array
    {
        return [];
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
        return $this->failures()->count() + ($this->rows - $this->successRows - $this->failures()->count());
    }
    public function getErrors()
    {
        return $this->failures();
    }
}
