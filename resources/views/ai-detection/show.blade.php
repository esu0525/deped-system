@extends('layouts.app')

@section('header_title', 'AI Alert Details')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div class="card glass">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="font-weight: 700;"><i class="fas fa-shield-virus text-primary"></i> AI Detection Report</h4>
                {!! $aiDetectionLog->risk_badge !!}
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Employee</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $aiDetectionLog->employee->full_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Department</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $aiDetectionLog->employee->department->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Risk Score</label>
                    <p style="font-weight: 800; font-size: 1.5rem; color: var(--primary); margin-top: 5px;">{{ $aiDetectionLog->risk_score }}/100</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Date Generated</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $aiDetectionLog->date_generated->format('F d, Y h:i A') }}</p>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

            <h5 style="font-weight: 700; margin-bottom: 15px;">Generated Reason</h5>
            <p style="line-height: 1.6;">{{ $aiDetectionLog->generated_reason }}</p>

            @if($aiDetectionLog->suspicious_flags && count($aiDetectionLog->suspicious_flags) > 0)
            <h5 style="font-weight: 700; margin: 25px 0 15px;">Suspicious Flags</h5>
            <ul style="list-style: none; padding: 0;">
                @foreach($aiDetectionLog->suspicious_flags as $flag)
                <li style="margin-bottom: 10px; display: flex; gap: 10px; align-items: flex-start;">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning); margin-top: 3px;"></i>
                    <span>{{ $flag }}</span>
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        <div>
            @if(!$aiDetectionLog->is_reviewed)
            <div class="card glass" style="margin-bottom: 20px;">
                <form action="{{ route('ai.review', $aiDetectionLog) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-check"></i> Mark as Reviewed
                    </button>
                </form>
            </div>
            @else
            <div class="card glass" style="margin-bottom: 20px; background: #f0fdf4;">
                <p style="font-weight: 700; color: var(--success);"><i class="fas fa-check-circle"></i> Reviewed</p>
                <small>By: {{ $aiDetectionLog->reviewer->name ?? 'N/A' }}</small><br>
                <small>On: {{ $aiDetectionLog->reviewed_at?->format('M d, Y h:i A') }}</small>
            </div>
            @endif

            <a href="{{ route('ai.index') }}" class="btn btn-secondary" style="width: 100%;">
                <i class="fas fa-arrow-left"></i> Back to Alerts
            </a>
        </div>
    </div>
</div>
@endsection
