<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Services\AccountGeneratorService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use SkipsFailures;

    protected $rows = 0;
    protected $successRows = 0;
    protected $departmentCache = [];

    public function model(array $row)
    {
        // Support both formats:
        // Format A (template): employee_id, full_name, ..., position, ...
        // Format B (user file): school, name, position

        $fullName = $row['full_name'] ?? $row['name'] ?? $row['full name'] ?? null;
        
        // Skip empty rows completely without counting them as total rows
        if (!$fullName || empty(trim($fullName))) {
            return null;
        }

        $this->rows++;

        $employeeIdInput = $row['employee_id'] ?? $row['employee id'] ?? $row['id'] ?? $row['reference_id'] ?? null;
        $position = $row['position'] ?? null;
        $school = $row['school'] ?? $row['department'] ?? $row['office'] ?? null;
        $contact = $row['contact_number'] ?? $row['contact'] ?? $row['phone'] ?? null;
        $address = $row['address'] ?? null;
        $status = $row['employment_status'] ?? $row['status'] ?? 'Permanent';
        $dateHiredInput = $row['date_hired'] ?? $row['date hired'] ?? null;

        // Find or create department/school if provided
        $departmentId = null;
        if ($school) {
            $schoolName = trim($school);
            
            if (isset($this->departmentCache[$schoolName])) {
                $departmentId = $this->departmentCache[$schoolName];
            } else {
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
                if ($department) {
                    $departmentId = $department->id;
                    $this->departmentCache[$schoolName] = $departmentId;
                }
            }
        }

        // Parse date_hired if it's an Excel serial number
        $parsedDate = null;
        if ($dateHiredInput) {
            try {
                if (is_numeric($dateHiredInput)) {
                    $parsedDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateHiredInput);
                }
                else {
                    $parsedDate = \Carbon\Carbon::parse($dateHiredInput);
                }
            }
            catch (\Exception $e) {
                $parsedDate = null;
            }
        }

        try {
            // Search for existing employee
            $employee = null;
            if ($employeeIdInput) {
                $employee = Employee::where('employee_id', $employeeIdInput)->first();
            }

            if (!$employee) {
                // Try searching by name and department to avoid duplicates
                $employee = Employee::where('full_name', trim($fullName))
                    ->when($departmentId, function($q) use ($departmentId) {
                        return $q->where('department_id', $departmentId);
                    })
                    ->first();
            }

            $data = [
                'full_name' => trim($fullName),
                'department_id' => $departmentId,
                'position' => $position ? trim($position) : null,
                'employment_status' => $status,
                'date_hired' => $parsedDate,
                'contact_number' => $contact,
                'address' => $address,
                'status' => 'Active',
            ];

            if ($employee) {
                $employee->update($data);
            } else {
                $data['employee_id'] = $employeeIdInput ?: self::generateUniqueId();
                $employee = Employee::create($data);
            }

            if ($employee) {
                $this->successRows++;
                
                // Secondary tasks - shouldn't fail the whole row import
                try {
                    app(\App\Services\LeaveCardService::class)->getOrCreateLeaveCard($employee);
                    if (!$employee->user_id) {
                        AccountGeneratorService::createAccountForEmployee($employee);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Post-import tasks failed for employee {$employee->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \Log::error("Import failed for row: " . json_encode($row) . " Error: " . $e->getMessage());
        }

        return null;
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
        return max(0, $this->rows - $this->successRows);
    }
    public function getErrors()
    {
        return $this->failures();
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
