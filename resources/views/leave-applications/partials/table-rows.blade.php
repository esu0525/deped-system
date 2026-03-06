@forelse($applications as $app)
<tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;">
    <td style="padding: 15px; font-weight: 700; color: var(--primary);">{{ $app->application_no }}</td>
    <td style="padding: 15px;">
        <p style="font-weight: 600; margin: 0; color: var(--dark);">{{ $app->employee->full_name }}</p>
        <small style="color: var(--secondary);">{{ $app->employee->employee_id }}</small>
    </td>
    <td style="padding: 15px;">
        <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            {{ $app->leaveType->name }}
        </span>
    </td>
    <td style="padding: 15px; font-size: 0.85rem;">
        {{ $app->date_from->format('M d') }} - {{ $app->date_to->format('M d, Y') }}
    </td>
    <td style="padding: 15px; font-weight: 700;">{{ $app->total_days }}</td>
    <td style="padding: 15px;">
        @php
            $statusColor = match($app->status) {
                'Approved' => '#059669',
                'Rejected' => '#dc2626',
                'Cancelled' => '#64748b',
                default => '#d97706'
            };
            $statusBg = match($app->status) {
                'Approved' => 'rgba(16, 185, 129, 0.1)',
                'Rejected' => 'rgba(220, 38, 38, 0.1)',
                'Cancelled' => 'rgba(100, 116, 139, 0.1)',
                default => 'rgba(217, 119, 6, 0.1)'
            };
        @endphp
        <span class="badge" style="background: {{ $statusBg }}; color: {{ $statusColor }}; padding: 6px 12px; border-radius: 8px;">
            {{ $app->status }}
        </span>
    </td>
    <td style="padding: 15px; font-size: 0.85rem; color: var(--secondary);">
        {{ $app->created_at->format('M d, Y') }}
    </td>
    <td style="padding: 15px; text-align: center;">
        <button type="button" class="btn btn-sm btn-secondary" onclick="openViewModal('{{ route('leave-applications.show', $app) }}')" title="View Details">
            <i class="fas fa-eye"></i>
        </button>
    </td>
</tr>
@empty
<tr>
    <td colspan="8" style="padding: 40px; text-align: center; color: var(--secondary);">
        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2;"></i>
        <p style="font-weight: 600;">No leave applications found.</p>
    </td>
</tr>
@endforelse

<!-- Pagination Links Update Identifier -->
<tr id="paginationLinksContainer" style="display: none;">
    <td>{{ $applications->links('vendor.pagination.custom') }}</td>
</tr>
