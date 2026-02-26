@extends('layouts.app')

@section('header_title', 'Statistical Reports')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
        <!-- Report Card 1 -->
        <div class="card glass animate-fade">
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(37, 99, 235, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h5 style="font-weight: 700;">Employee Leave Summary</h5>
            </div>
            <p style="font-size: 0.85rem; color: var(--secondary); margin-bottom: 20px;">Generate a complete list of employees with their current leave balances and status.</p>
            <form action="{{ route('reports.employee-summary') }}" method="GET">
                <div class="form-group">
                    <select name="department" class="form-control">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-primary" style="width: 100%;">Generate Report</button>
            </form>
        </div>

        <!-- Report Card 2 -->
        <div class="card glass animate-fade" style="animation-delay: 0.1s;">
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-calendar-days"></i>
                </div>
                <h5 style="font-weight: 700;">Monthly Usage Breakdown</h5>
            </div>
            <p style="font-size: 0.85rem; color: var(--secondary); margin-bottom: 20px;">Detailed analysis of leave types used per department in a specific month.</p>
            <form action="{{ route('reports.monthly-leave') }}" method="GET">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <select name="month" class="form-control">
                        @for($i=1; $i<=12; $i++)
                            <option value="{{ $i }}" {{ date('m') == $i ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                        @endfor
                    </select>
                    <input type="number" name="year" class="form-control" value="{{ date('Y') }}">
                </div>
                <button type="submit" class="btn btn-outline-primary" style="width: 100%;">Analyze Trends</button>
            </form>
        </div>

        <!-- Report Card 3 -->
        <div class="card glass animate-fade" style="animation-delay: 0.2s;">
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: var(--warning); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-file-export"></i>
                </div>
                <h5 style="font-weight: 700;">Bulk Export (Excel)</h5>
            </div>
            <p style="font-size: 0.85rem; color: var(--secondary); margin-bottom: 20px;">Download direct raw data for external processing or manual auditing.</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ route('reports.export.employees') }}" class="btn btn-light" style="justify-content: flex-start; background: #fff;">
                    <i class="fas fa-download text-primary"></i> Employees Masterlist
                </a>
                <a href="{{ route('reports.export.leave-applications') }}" class="btn btn-light" style="justify-content: flex-start; background: #fff;">
                    <i class="fas fa-download text-primary"></i> Approved Applications
                </a>
                <a href="{{ route('reports.export.leave-transactions') }}" class="btn btn-light" style="justify-content: flex-start; background: #fff;">
                    <i class="fas fa-download text-primary"></i> Ledger Transactions
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Insights -->
    <div class="card" style="margin-top: 30px;">
        <h4 style="font-weight: 700; margin-bottom: 25px;">Top Leave Consumers (Current Year)</h4>
        <div class="table-responsive">
            <table class="table" style="width: 100%;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #eee;">
                        <th style="padding: 12px;">Employee</th>
                        <th style="padding: 12px;">Department</th>
                        <th style="padding: 12px;">VL Used</th>
                        <th style="padding: 12px;">SL Used</th>
                        <th style="padding: 12px;">Total Days</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                    $topUsers = \App\Models\LeaveCard::with('employee.department')
                        ->where('year', now()->year)
                        ->orderByRaw('(vl_used + sl_used) DESC')
                        ->take(5)
                        ->get();
                    @endphp
                    @foreach($topUsers as $top)
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 12px; font-weight: 600;">{{ $top->employee->full_name }}</td>
                        <td style="padding: 12px;">{{ $top->employee->department?->code }}</td>
                        <td style="padding: 12px;">{{ number_format($top->vl_used, 2) }}</td>
                        <td style="padding: 12px;">{{ number_format($top->sl_used, 2) }}</td>
                        <td style="padding: 12px;">
                            <span class="badge badge-info" style="font-weight: 700;">{{ number_format($top->vl_used + $top->sl_used, 2) }} Days</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
