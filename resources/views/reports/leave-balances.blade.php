@extends('layouts.app')
@section('header_title', 'Leave Balances Report')
@section('content')
<div class="animate-fade">
    <div class="card glass" style="margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
            <select name="department" class="form-control" style="width: 200px;" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </form>
    </div>
    <div class="card glass">
        <h4 style="font-weight: 700; margin-bottom: 20px;"><i class="fas fa-wallet text-primary"></i> Leave Balances ({{ $year }})</h4>
        <table class="table">
            <thead><tr><th>Employee</th><th>Department</th><th style="text-align:center;">VL Bal</th><th style="text-align:center;">SL Bal</th><th style="text-align:center;">Total</th></tr></thead>
            <tbody>
                @forelse($employees as $emp)
                @php $card = $emp->leaveCards->first(); @endphp
                <tr>
                    <td><strong>{{ $emp->full_name }}</strong></td>
                    <td>{{ $emp->department->name ?? 'N/A' }}</td>
                    <td style="text-align:center; font-weight:700;">{{ $card ? number_format($card->vl_balance, 3) : '0.000' }}</td>
                    <td style="text-align:center; font-weight:700;">{{ $card ? number_format($card->sl_balance, 3) : '0.000' }}</td>
                    <td style="text-align:center; font-weight:800; color:var(--primary);">{{ $card ? number_format($card->vl_balance + $card->sl_balance, 3) : '0.000' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--secondary);">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
