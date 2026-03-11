@forelse($employees as $emp)
<tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'" onclick="if(!event.target.closest('button') && !event.target.closest('a')) openViewModal('{{ route('employees.show', $emp) }}')">
    <td style="padding: 15px; font-weight: 700; color: var(--primary);">{{ $emp->employee_id }}</td>
    <td style="padding: 15px;">
        <div>
            <p style="font-weight: 600; margin: 0; color: var(--dark);">{{ $emp->full_name }}</p>
            @if($emp->user)
                <div style="display: flex; align-items: center; gap: 4px; font-size: 0.68rem; color: #2563eb; margin-top: 2px;">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ $emp->user->email }}</span>
                </div>
            @else
                <div style="display: flex; align-items: center; gap: 4px; font-size: 0.68rem; color: #94a3b8; margin-top: 2px;">
                    <i class="far fa-user-circle"></i>
                    <span>No Account</span>
                </div>
            @endif
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
    <td>{{ $employees->links('vendor.pagination.custom') }}</td>
</tr>
