@extends('layouts.app')

@section('header_title', 'User Management')

@section('content')
<div class="animate-fade">
    <div class="header-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div class="tab-pill-container" style="display: flex; background: #f1f5f9; padding: 5px; border-radius: 12px; gap: 5px;">
            <a href="{{ route('users.index') }}" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: #1e293b; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-users"></i> USERS
            </a>
            <a href="#" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; transition: all 0.2s; display: flex; align-items: center; gap: 8px; opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-user-tag"></i> ROLES
            </a>
            <a href="{{ route('audit-trail.index') }}" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-history"></i> AUDIT LOGS
            </a>
        </div>

        <div style="margin-bottom: 8px;">
            <form action="{{ route('users.index') }}" method="GET" style="display: flex; gap: 10px; align-items: center;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 0.85rem;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search users..." 
                           style="padding: 8px 12px 8px 35px; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 0.85rem; width: 250px; outline: none; transition: border-color 0.2s;">
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="padding: 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; background: #fff; display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; background: #f0f9ff; color: #0369a1; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-users-cog"></i>
            </div>
            <h4 style="margin: 0; font-weight: 700; color: #1e293b; font-size: 1.1rem;">List of Users</h4>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.025em;">Username</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.025em;">Name</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.025em;">Email</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.025em;">Role</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.025em;">Status</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.025em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div style="padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #f8fafc;">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
