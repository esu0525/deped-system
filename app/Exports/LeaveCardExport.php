<?php

namespace App\Exports;

use App\Models\LeaveCard;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveCardExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $year = $this->filters['year'] ?? now()->year;

        $query = LeaveCard::with('employee.department')
            ->where('year', $year);

        if (!empty($this->filters['department'])) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $this->filters['department']));
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Year',
            'VL Beginning Balance',
            'VL Earned',
            'VL Used',
            'VL Balance',
            'SL Beginning Balance',
            'SL Earned',
            'SL Used',
            'SL Balance',
            'Forced Leave Balance',
            'Special Leave Balance',
        ];
    }

    public function map($card): array
    {
        return [
            $card->employee?->employee_id,
            $card->employee?->full_name,
            $card->employee?->department?->name ?? 'N/A',
            $card->year,
            $card->vl_beginning_balance,
            $card->vl_earned,
            $card->vl_used,
            $card->vl_balance,
            $card->sl_beginning_balance,
            $card->sl_earned,
            $card->sl_used,
            $card->sl_balance,
            $card->forced_leave_balance,
            $card->special_leave_balance,
        ];
    }
}
