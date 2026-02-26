@extends('layouts.app')
@section('header_title', 'Monthly Leave Report')
@section('content')
<div class="animate-fade">
    <div class="card glass" style="margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
            <select name="month" class="form-control" style="width: 150px;" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
            <input type="number" name="year" class="form-control" style="width: 100px;" value="{{ $year }}" onchange="this.form.submit()">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </form>
    </div>
    <div class="card glass">
        <h4 style="font-weight: 700; margin-bottom: 20px;"><i class="fas fa-calendar text-primary"></i> Approved Leaves - {{ date('F', mktime(0, 0, 0, $month, 1)) }} {{ $year }}</h4>
        <table class="table">
            <thead><tr><th>Employee</th><th>Department</th><th>Leave Type</th><th>Date</th><th>Days</th></tr></thead>
            <tbody>
                @forelse($applications as $app)
                <tr>
                    <td><strong>{{ $app->employee->full_name ?? 'N/A' }}</strong></td>
                    <td>{{ $app->employee->department->name ?? 'N/A' }}</td>
                    <td><span class="badge badge-info">{{ $app->leaveType->name ?? 'N/A' }}</span></td>
                    <td><small>{{ $app->date_from->format('M d') }} - {{ $app->date_to->format('M d') }}</small></td>
                    <td><strong>{{ $app->num_days }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--secondary);">No approved leaves this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
