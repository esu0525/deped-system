<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use App\Imports\LeaveCardImport;
use App\Imports\LeaveTransactionsImport;

class ImportController extends Controller
{
    public function index()
    {
        $importLogs = ImportLog::with('user')->latest()->paginate(15);
        return view('import.import-manager', compact('importLogs'));
    }

    public function downloadTemplate(string $type)
    {
        $templates = [
            'employees' => 'employees_import_template.xlsx',
            'leave_cards' => 'leave_cards_import_template.xlsx',
            'leave_transactions' => 'leave_transactions_import_template.xlsx',
        ];

        if (!isset($templates[$type])) {
            abort(404);
        }

        $path = storage_path('app/templates/' . $templates[$type]);

        if (!file_exists($path)) {
            // Generate template on the fly
            return $this->generateTemplate($type);
        }

        return response()->download($path);
    }

    protected function generateTemplate(string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $exports = [
            'employees' => \App\Exports\Templates\EmployeesTemplateExport::class ,
            'leave_cards' => \App\Exports\Templates\LeaveCardsTemplateExport::class ,
            'leave_transactions' => \App\Exports\Templates\LeaveTransactionsTemplateExport::class ,
        ];

        $filename = str_replace('_', '-', $type) . '-template.xlsx';
        return Excel::download(new $exports[$type](), $filename);
    }

    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $import = new EmployeesImport();
        Excel::import($import, $request->file('file'));

        $log = ImportLog::create([
            'user_id' => auth()->id(),
            'import_type' => 'Employees',
            'filename' => $request->file('file')->getClientOriginalName(),
            'total_rows' => $import->getTotalRows(),
            'success_rows' => $import->getSuccessRows(),
            'failed_rows' => $import->getFailedRows(),
            'errors' => $import->getErrors(),
            'status' => $import->getFailedRows() > 0 ? 'Completed' : 'Completed',
        ]);

        AuditTrail::log('IMPORT', 'Import', "Imported employees: {$import->getSuccessRows()} success, {$import->getFailedRows()} failed");

        return redirect()->route('employees.index')->with('success', "Import completed: {$import->getSuccessRows()} employees imported, {$import->getFailedRows()} failed.");
    }

    public function importLeaveCards(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $import = new LeaveCardImport();
        Excel::import($import, $request->file('file'));

        ImportLog::create([
            'user_id' => auth()->id(),
            'import_type' => 'Leave Cards',
            'filename' => $request->file('file')->getClientOriginalName(),
            'total_rows' => $import->getTotalRows(),
            'success_rows' => $import->getSuccessRows(),
            'failed_rows' => $import->getFailedRows(),
            'errors' => $import->getErrors(),
            'status' => 'Completed',
        ]);

        AuditTrail::log('IMPORT', 'Import', "Imported leave cards: {$import->getSuccessRows()} success, {$import->getFailedRows()} failed");

        return redirect()->route('import.index')->with('success', "Leave cards imported: {$import->getSuccessRows()} success, {$import->getFailedRows()} failed.");
    }

    public function importLeaveTransactions(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $import = new LeaveTransactionsImport();
        Excel::import($import, $request->file('file'));

        ImportLog::create([
            'user_id' => auth()->id(),
            'import_type' => 'Leave Transactions',
            'filename' => $request->file('file')->getClientOriginalName(),
            'total_rows' => $import->getTotalRows(),
            'success_rows' => $import->getSuccessRows(),
            'failed_rows' => $import->getFailedRows(),
            'errors' => $import->getErrors(),
            'status' => 'Completed',
        ]);

        AuditTrail::log('IMPORT', 'Import', "Imported leave transactions: {$import->getSuccessRows()} success, {$import->getFailedRows()} failed");

        return redirect()->route('import.index')->with('success', "Leave transactions imported: {$import->getSuccessRows()} success, {$import->getFailedRows()} failed.");
    }
}
