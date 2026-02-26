<?php

namespace App\Exports;

use App\Models\LeaveTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveTransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = LeaveTransaction::with(['employee', 'leaveType', 'leaveCard']);

        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('transaction_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('transaction_date', '<=', $this->filters['date_to']);
        }

        return $query->latest('transaction_date')->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Leave Type',
            'Year',
            'Transaction Date',
            'Transaction Type',
            'Days',
            'VL Balance After',
            'SL Balance After',
            'Remarks',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->employee?->employee_id,
            $transaction->employee?->full_name,
            $transaction->leaveType?->name,
            $transaction->leaveCard?->year,
            $transaction->transaction_date?->format('Y-m-d'),
            $transaction->transaction_type,
            $transaction->days,
            $transaction->vl_balance_after,
            $transaction->sl_balance_after,
            $transaction->remarks,
        ];
    }
}
