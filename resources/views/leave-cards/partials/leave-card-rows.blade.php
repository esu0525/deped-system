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
