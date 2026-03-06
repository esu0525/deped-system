<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use App\Models\AuditTrail;

class ImportController extends Controller
{
    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new EmployeesImport;
            Excel::import($import, $request->file('file'));

            $success = $import->getSuccessRows();
            $failed = $import->getFailedRows();

            AuditTrail::log('IMPORT', 'Employee Management', "Imported {$success} employees from file. ({$failed} failed)");

            return back()->with('success', "Import completed. {$success} imported, {$failed} failed.");
        } catch (\Exception $e) {
            return back()->with('error', 'Import error: ' . $e->getMessage());
        }
    }
}
