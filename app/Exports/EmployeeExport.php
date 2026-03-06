<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function view(): View
    {
        return view('exports.employees', [
            'employees' => $this->employees
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Select all cells and apply Cambria font
        return [
            1    => ['font' => ['bold' => true, 'name' => 'Cambria', 'size' => 12]],
            'A:E' => ['font' => ['name' => 'Cambria', 'size' => 11]],
        ];
    }
}
