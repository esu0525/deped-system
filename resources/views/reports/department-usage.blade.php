@extends('layouts.app')
@section('header_title', 'Department Usage Report')
@section('content')
<div class="animate-fade">
    <div class="card glass" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <span style="font-weight: 700;">Year: {{ $year }}</span>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        @foreach($departments as $dept)
        <div class="card glass">
            <h5 style="font-weight: 700; color: var(--primary); margin-bottom: 15px;">{{ $dept->name }}</h5>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: var(--secondary);">Employees</span>
                <strong>{{ $dept->employees->count() }}</strong>
            </div>
            @php
                $deptCards = $dept->employees->flatMap->leaveCards;
                $totalVlUsed = $deptCards->sum('vl_used');
                $totalSlUsed = $deptCards->sum('sl_used');
            @endphp
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: var(--secondary);">VL Used</span>
                <strong>{{ number_format($totalVlUsed, 3) }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--secondary);">SL Used</span>
                <strong>{{ number_format($totalSlUsed, 3) }}</strong>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
