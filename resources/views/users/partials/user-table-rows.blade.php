@forelse($users as $user)
<tr style="border-bottom: 1px solid var(--border-color); transition: all 0.3s; vertical-align: middle; height: 80px;">
    <td style="padding: 15px 15px 15px 25px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            @if($user->avatar)
                <div style="width: 42px; height: 42px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: 2px solid white; flex-shrink: 0;">
                    <img src="{{ asset('storage/' . $user->avatar) }}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @else
                @php
                    $initials = collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    $colors = ['#6366f1', '#a855f7', '#ec4899', '#3b82f6', '#10b981'];
                    $bgColor = $colors[$user->id % count($colors)];
                @endphp
                <div style="width: 42px; height: 42px; border-radius: 12px; background: {{ $bgColor }}; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 0.85rem; box-shadow: 0 4px 10px {{ $bgColor }}44; flex-shrink: 0;">
                    {{ strtoupper($initials) }}
                </div>
            @endif
            <div style="overflow: hidden;">
                <div style="font-weight: 800; color: var(--text-main); font-size: 0.95rem; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->name }}</div>
                <div style="font-size: 0.75rem; color: var(--secondary); font-weight: 500; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->email }}</div>
            </div>
        </div>
    </td>
    <td style="padding: 15px; text-align: center;">
        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; background: rgba(99, 102, 241, 0.1); border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.15);">
                 <i class="fas fa-shield-halved" style="font-size: 0.6rem; color: #6366f1;"></i>
                 <span style="font-size: 0.65rem; color: #6366f1; font-weight: 800; text-transform: uppercase;">{{ str_replace('_', ' ', $user->role) }}</span>
            </div>
            @if($user->assign)
            <div style="font-size: 0.6rem; color: #a855f7; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                <i class="fas fa-location-dot" style="margin-right: 2px;"></i>{{ $user->assign }}
            </div>
            @endif
        </div>
    </td>
    <td style="padding: 15px; text-align: center;">
        @if($user->access)
            @php
                $accessArr = explode(', ', $user->access);
                $count = count($accessArr);
            @endphp
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 4px;">
                @if($count <= 1)
                    <span style="font-size: 0.7rem; color: #64748b; font-weight: 700; background: var(--bg-body); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                        {{ $user->access }}
                    </span>
                @else
                    <span style="font-size: 0.7rem; color: #1e293b; font-weight: 800; background: #fef9c3; color: #854d0e; padding: 4px 10px; border-radius: 6px; border: 1px solid #fde047;" title="{{ $user->access }}">
                        {{ $count }} Positions
                    </span>
                @endif
            </div>
        @else
            <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.7rem; color: #92400e; font-weight: 800; background: linear-gradient(to right, #fef9c3, #fde68a); padding: 5px 12px; border-radius: 8px; border: 1px solid #fde047; box-shadow: 0 2px 4px rgba(234, 179, 8, 0.1);">
                <i class="fas fa-crown" style="font-size: 0.65rem;"></i> Full System Access
            </span>
        @endif
    </td>
    <td style="padding: 15px; text-align: center;">
        @if($user->is_active)
            <span style="padding: 6px 12px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                <i class="fas fa-check-circle" style="margin-right: 4px;"></i>Active
            </span>
        @else
            <span style="padding: 6px 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                <i class="fas fa-times-circle" style="margin-right: 4px;"></i>Inactive
            </span>
        @endif
    </td>
    <td style="padding: 15px; text-align: center;">
        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">
            {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Never' }}
        </div>
    </td>
    <td style="padding: 15px; text-align: center;">
        <div style="font-size: 0.85rem; color: #1e293b; font-weight: 700;">{{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y') }}</div>
        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 500; margin-top: 2px;">at {{ \Carbon\Carbon::parse($user->created_at)->format('h:i A') }}</div>
    </td>
    <td style="padding: 15px 25px; text-align: right;">
        <div style="display: flex; gap: 6px; justify-content: flex-end;">
            <button onclick="viewUser('{{ $user->id }}')" title="View Account Profile" class="header-icon-btn">
                <i class="fas fa-eye" style="font-size: 0.8rem;"></i>
            </button>
            
            @if(auth()->id() !== $user->id)
                @if($user->is_active)
                    <button onclick="toggleUserStatus('{{ $user->id }}', 'deactivate')" title="Deactivate Account" class="header-icon-btn" style="color: #f59e0b;">
                        <i class="fas fa-user-minus" style="font-size: 0.8rem;"></i>
                    </button>
                @else
                    <button onclick="toggleUserStatus('{{ $user->id }}', 'activate')" title="Activate Account" class="header-icon-btn" style="color: #10b981;">
                        <i class="fas fa-user-plus" style="font-size: 0.8rem;"></i>
                    </button>
                @endif
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" style="padding: 80px; text-align: center;">
        <div style="font-size: 3rem; color: var(--border-color); margin-bottom: 20px;"><i class="fas fa-user-slash"></i></div>
        <div style="font-weight: 800; color: var(--secondary); font-size: 1.1rem;">No accounts found</div>
    </td>
</tr>
@endforelse
