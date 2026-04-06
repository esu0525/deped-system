@forelse($users as $user)
<tr style="border-bottom: 1px solid #f8fafc; transition: all 0.3s; vertical-align: middle;">
    <td style="padding: 20px 15px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; box-shadow: 0 4px 10px rgba(168, 85, 247, 0.2);">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <div style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">{{ $user->name }}</div>
                <div style="font-size: 0.75rem; color: #64748b;">
                    <i class="fas fa-envelope" style="font-size: 0.65rem;"></i> {{ $user->email }}
                </div>
            </div>
        </div>
    </td>
    <td style="padding: 20px 15px;">
        <span style="padding: 5px 12px; background: #f1f5f9; border-radius: 8px; font-size: 0.7rem; color: #475569; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #e2e8f0;">
            {{ $user->role }}
        </span>
    </td>
    <td style="padding: 20px 15px;">
        @if($user->is_active)
            <span style="display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #059669; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;">
                <span style="width: 6px; height: 6px; background: #059669; border-radius: 50%;"></span> Active
            </span>
        @else
            <span style="display: inline-flex; align-items: center; gap: 5px; background: #fef2f2; color: #dc2626; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;">
                <span style="width: 6px; height: 6px; background: #dc2626; border-radius: 50%;"></span> Inactive
            </span>
        @endif
        @if($user->access)
            <div style="font-size: 0.65rem; color: #6366f1; margin-top: 6px; font-weight: 800; background: #edf2ff; padding: 2px 8px; border-radius: 4px; display: inline-block;">
                <i class="fas fa-shield-alt" style="font-size: 0.6rem;"></i> {{ $user->access }}
            </div>
        @endif
    </td>
    <td style="padding: 20px 15px; text-align: center;">
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn-modern-edit" onclick="openEditUserModal('{{ $user->id }}')" title="Edit User">
                <i class="fas fa-pen"></i>
            </button>
            <button class="btn-modern-delete" onclick="deleteUser('{{ $user->id }}', '{{ $user->name }}')" title="Delete User">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="4" style="padding: 60px; text-align: center;">
        <div style="font-size: 4rem; color: #f1f5f9; margin-bottom: 15px;">
            <i class="fas fa-user-shield"></i>
        </div>
        <p style="color: #94a3b8; font-weight: 600;">No system users found matching your search.</p>
    </td>
</tr>
@endforelse

<tr id="paginationLinksContainer" style="display: none;">
    <td colspan="4">
        {{ $users->links('vendor.pagination.custom') }}
    </td>
</tr>

<style>
    .btn-modern-edit {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: none;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-modern-edit:hover {
        background: #e2e8f0;
        color: var(--primary);
        transform: translateY(-2px);
    }
    .btn-modern-delete {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: none;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-modern-delete:hover {
        background: #fecaca;
        transform: translateY(-2px);
    }
</style>
