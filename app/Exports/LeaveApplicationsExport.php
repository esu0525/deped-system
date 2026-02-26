<?php

namespace App\Exports;

use App\Models\LeaveApplication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveApplicationsExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = LeaveApplication::with(['employee.department', 'leaveType', 'approver']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['leave_type'])) {
            $query->where('leave_type_id', $this->filters['leave_type']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('date_from', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('date_to', '<=', $this->filters['date_to']);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Application No.',
            'Employee ID',
            'Employee Name',
            'Department',
            'Leave Type',
            'Date From',
            'Date To',
            'No. of Days',
            'Reason',
            'Status',
            'Approved By',
            'Remarks',
        ];
    }

    public function map($app): array
    {
        return [
            $app->application_no,
            $app->employee?->employee_id,
            $app->employee?->full_name,
            $app->employee?->department?->name ?? 'N/A',
            $app->leaveType?->name,
            $app->date_from?->format('Y-m-d'),
            $app->date_to?->format('Y-m-d'),
            $app->num_days,
            $app->reason,
            $app->status,
            $app->approver?->name,
            $app->remarks,
        ];
    }
}
