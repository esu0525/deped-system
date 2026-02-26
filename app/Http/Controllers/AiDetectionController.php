<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AiDetectionLog;
use App\Models\AuditTrail;
use App\Services\AiDetectionService;
use Illuminate\Http\Request;

class AiDetectionController extends Controller
{
    protected AiDetectionService $aiService;

    public function __construct(AiDetectionService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $query = AiDetectionLog::with('employee.department');

        if ($request->risk_level) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->reviewed !== null) {
            $query->where('is_reviewed', $request->reviewed === 'yes');
        }

        $logs = $query->latest('date_generated')->paginate(15)->withQueryString();

        $stats = [
            'high' => AiDetectionLog::where('risk_level', 'High')->count(),
            'medium' => AiDetectionLog::where('risk_level', 'Medium')->count(),
            'low' => AiDetectionLog::where('risk_level', 'Low')->count(),
            'unreviewed' => AiDetectionLog::where('is_reviewed', false)->where('risk_level', '!=', 'Low')->count(),
        ];

        return view('ai-detection.ai-monitor', compact('logs', 'stats'));
    }

    public function show(AiDetectionLog $aiDetectionLog)
    {
        $aiDetectionLog->load('employee.department', 'reviewer');
        return view('ai-detection.show', compact('aiDetectionLog'));
    }

    public function analyze(Employee $employee)
    {
        $log = $this->aiService->analyze($employee);

        AuditTrail::log('AI_ANALYZE', 'AI Detection', "Ran AI analysis for {$employee->full_name}. Risk: {$log->risk_level} ({$log->risk_score}/100)");

        return back()->with('success', "AI analysis completed. Risk Level: {$log->risk_level} (Score: {$log->risk_score}/100)");
    }

    public function analyzeAll()
    {
        $count = $this->aiService->analyzeAll();

        AuditTrail::log('AI_ANALYZE_ALL', 'AI Detection', "Ran AI analysis for all {$count} active employees");

        return back()->with('success', "AI analysis completed for {$count} employees.");
    }

    public function markReviewed(AiDetectionLog $aiDetectionLog)
    {
        $aiDetectionLog->update([
            'is_reviewed' => true,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditTrail::log('REVIEW', 'AI Detection', "Marked AI alert as reviewed for employee ID {$aiDetectionLog->employee_id}");

        return back()->with('success', 'Alert marked as reviewed.');
    }
}
