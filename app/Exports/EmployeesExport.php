<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Employee::with('department')->where('status', 'Active');

        if (!empty($this->filters['department'])) {
            $query->where('department_id', $this->filters['department']);
        }

        return $query->orderBy('full_name')->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Full Name',
            'Gender',
            'Position',
            'Department',
            'Employment Status',
            'Date Hired',
            'Email',
            'Contact Number',
            'Status',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_id,
            $employee->full_name,
            $employee->gender,
            $employee->position,
            $employee->department?->name ?? 'N/A',
            $employee->employment_status,
            $employee->date_hired?->format('Y-m-d'),
            $employee->email,
            $employee->contact_number,
            $employee->status,
        ];
    }
}
