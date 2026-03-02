@forelse($employees as $emp)
<tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;">
    <td style="padding: 15px; font-weight: 700; color: var(--primary);">{{ $emp->employee_id }}</td>
    <td style="padding: 15px;">
        <div>
            <p style="font-weight: 600; margin: 0; color: var(--dark);">{{ $emp->full_name }}</p>
            @if($emp->email) <small style="color: var(--secondary);">{{ $emp->email }}</small> @endif
        </div>
    </td>
    <td style="padding: 15px; font-size: 0.85rem;">{{ $emp->department?->name ?? 'N/A' }}</td>
    <td style="padding: 15px; font-size: 0.85rem;">{{ $emp->position }}</td>
    <td style="padding: 15px;">
        <span class="badge" style="background: {{ $emp->status == 'Active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(100, 116, 139, 0.1)' }}; color: {{ $emp->status == 'Active' ? '#059669' : '#475569' }}; padding: 6px 12px; border-radius: 8px; font-size: 0.72rem; font-weight: 700;">
            {{ $emp->status }}
        </span>
    </td>
    <td style="padding: 15px; text-align: center;">
        <div style="display: flex; gap: 8px; justify-content: center;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="openViewModal('{{ route('employees.show', $emp) }}')" title="View Employee">
                <i class="fas fa-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="openEditModal('{{ route('employees.edit', $emp) }}')" title="Edit Employee">
                <i class="fas fa-edit"></i>
            </button>
            <a href="{{ route('leave-cards.show', $emp) }}" class="btn btn-sm btn-secondary" title="View Leave Ledger" target="_self">
                <i class="fas fa-address-card"></i>
            </a>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" style="padding: 40px; text-align: center; color: var(--secondary);">
        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2;"></i>
        <p style="font-weight: 600;">No employees found matching your search.</p>
    </td>
</tr>
@endforelse

<!-- Pagination Link Update Identifier -->
<tr id="paginationLinksContainer" style="display: none;">
    <td>{{ $employees->links() }}</td>
</tr>
