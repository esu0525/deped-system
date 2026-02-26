<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\LeaveApplication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonthlyDeptSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $month = $this->filters['month'] ?? now()->month;
        $year = $this->filters['year'] ?? now()->year;

        return Department::with(['employees' => function ($q) use ($month, $year) {
            $q->withCount(['leaveApplications as approved_leaves_count' => function ($q2) use ($month, $year) {
                $q2->where('status', 'Approved')
                    ->whereYear('date_from', $year)
                    ->whereMonth('date_from', $month);
            }]);
            $q->withSum(['leaveApplications as total_leave_days' => function ($q2) use ($month, $year) {
                $q2->where('status', 'Approved')
                    ->whereYear('date_from', $year)
                    ->whereMonth('date_from', $month);
            }], 'num_days');
        }])->where('is_active', true)->get();
    }

    public function headings(): array
    {
        return [
            'Department',
            'Total Employees',
            'Approved Leave Applications',
            'Total Leave Days',
        ];
    }

    public function map($department): array
    {
        return [
            $department->name,
            $department->employees->count(),
            $department->employees->sum('approved_leaves_count'),
            $department->employees->sum('total_leave_days') ?? 0,
        ];
    }
}
