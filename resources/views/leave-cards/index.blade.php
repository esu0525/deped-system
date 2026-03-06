@extends('layouts.app')

@section('header_title', 'Leave Ledger')

@section('content')
<div class="animate-fade">
    <!-- Search -->
    <div class="card glass animate-fade" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <form method="GET" action="{{ route('leave-cards.index') }}" style="display: flex; gap: 12px; align-items: center;">
                <input type="text" name="search" class="form-control" placeholder="Search employee name or ID..." value="{{ request('search') }}" style="width: 300px;">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Search</button>
            </form>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="badge badge-info" style="font-size: 0.8rem;"><i class="fas fa-calendar"></i> Year {{ now()->year }}</span>
            </div>
        </div>
    </div>

    <!-- Employee Leave Cards Table -->
    <div class="card glass animate-fade">
        <h4 style="font-weight: 700; margin-bottom: 20px;"><i class="fas fa-address-card text-primary"></i> Employee Leave Credits</h4>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th style="text-align: center;">VL Balance</th>
                        <th style="text-align: center;">SL Balance</th>

                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    @php
                        $card = $employee->leaveCards->where('year', now()->year)->first();
                        $vlBal = $card ? $card->vl_balance : 0;
                        $slBal = $card ? $card->sl_balance : 0;
                    @endphp
                    <tr>
                        <td><strong style="color: var(--primary);">{{ $employee->employee_id }}</strong></td>
                        <td>
                            <div>
                                <strong>{{ $employee->full_name }}</strong>
                                <br><small style="color: var(--secondary);">{{ $employee->department->name ?? 'No Department' }}</small>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span style="font-weight: 700; color: {{ $vlBal > 5 ? 'var(--success)' : ($vlBal > 0 ? 'var(--warning)' : 'var(--danger)') }};">
                                {{ number_format($vlBal, 3) }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span style="font-weight: 700; color: {{ $slBal > 5 ? 'var(--success)' : ($slBal > 0 ? 'var(--warning)' : 'var(--danger)') }};">
                                {{ number_format($slBal, 3) }}
                            </span>
                        </td>

                        <td style="text-align: center;">
                            <a href="{{ route('leave-cards.show', $employee) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Ledger
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--secondary);">
                            <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                            <p style="font-weight: 600;">No employees found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $employees->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>
@endsection
