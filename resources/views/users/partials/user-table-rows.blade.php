@forelse($users as $user)
<tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;">
    <td style="padding: 16px 25px; font-weight: 600; color: #1e293b; font-size: 0.9rem;">{{ $user->username }}</td>
    <td style="padding: 16px 25px; font-weight: 500; color: #64748b; font-size: 0.9rem;">{{ $user->name }}</td>
    <td style="padding: 16px 25px; font-weight: 500; color: #64748b; font-size: 0.9rem;">{{ $user->email }}</td>
    <td style="padding: 16px 25px;">
        <span style="font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 4px; text-transform: lowercase;">{{ $user->role }}</span>
    </td>
    <td style="padding: 16px 25px;">
        @if($user->is_active)
            <span style="background: #ecfdf5; color: #059669; font-size: 0.7rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid #d1fae5;">Active</span>
        @else
            <span style="background: #fef2f2; color: #dc2626; font-size: 0.7rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid #fee2e2;">Inactive</span>
        @endif
    </td>
    <td style="padding: 16px 25px;">
        <div style="display: flex; gap: 8px;">
            @if($user->is_active)
                <form action="{{ route('users.deactivate', $user->id) }}" method="POST" onsubmit="return confirm('Deactivate this user account?')">
                    @csrf
                    <button type="submit" style="background: #fff; border: 1px solid #fee2e2; color: #ef4444; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.2s;">
                        <i class="fas fa-user-minus"></i> Deactivate
                    </button>
                </form>
            @else
                <form action="{{ route('users.activate', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" style="background: #fff; border: 1px solid #d1fae5; color: #059669; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.2s;">
                        <i class="fas fa-user-plus"></i> Activate
                    </button>
                </form>
            @endif
            
            @if(auth()->id() !== $user->id)
            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete this user?')">
                @csrf
                @method('DELETE')
                <button type="submit" style="background: #fff; border: 1px solid #e2e8f0; color: #94a3b8; padding: 6px 10px; border-radius: 6px; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">No users found.</td>
</tr>
@endforelse

@if($users->hasPages())
<tr id="paginationLinksContainer" style="display: none;">
    <td colspan="6">
        {{ $users->links('vendor.pagination.custom') }}
    </td>
</tr>
@endif
