@forelse($users as $user)
<tr style="border-bottom: 1px solid var(--border-color); transition: all 0.3s; vertical-align: middle; height: 80px;">
    <td style="padding: 15px 25px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            @if($user->avatar)
                <div style="width: 45px; height: 45px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 2px solid white;">
                    <img src="{{ asset('storage/' . $user->avatar) }}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @else
                @php
                    $initials = collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    $colors = ['#6366f1', '#a855f7', '#ec4899', '#f59e0b', '#10b981'];
                    $bgColor = $colors[$user->id % count($colors)];
                @endphp
                <div style="width: 45px; height: 45px; border-radius: 12px; background: {{ $bgColor }}; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 0.9rem; box-shadow: 0 4px 10px {{ $bgColor }}44;">
                    {{ strtoupper($initials) }}
                </div>
            @endif
            <div>
                <div style="font-weight: 800; color: var(--text-main); font-size: 0.95rem; line-height: 1.2;">{{ $user->name }}</div>
                <div style="font-size: 0.8rem; color: var(--secondary); font-weight: 500; margin-top: 3px;">{{ $user->email }}</div>
            </div>
        </div>
    </td>
    <td style="padding: 15px; text-align: center;">
        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: rgba(59, 130, 246, 0.1); border-radius: 10px; border: 1px solid var(--border-color);">
             <i class="fas fa-shield-halved" style="font-size: 0.65rem; color: var(--primary);"></i>
             <span style="font-size: 0.7rem; color: var(--primary); font-weight: 800; text-transform: uppercase;">{{ str_replace('_', ' ', $user->role) }}</span>
        </div>
    </td>
    <td style="padding: 15px; text-align: center;">
        <span style="font-size: 0.75rem; color: #818cf8; font-weight: 600; font-style: italic; max-width: 140px; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $user->access ?: 'Full Access Level' }}">
            {{ $user->access ?: 'Full Access Level' }}
        </span>
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
            <button onclick="deleteUser('{{ $user->id }}', '{{ $user->name }}')" title="Remove Account" class="header-icon-btn" style="color: var(--danger);">
                <i class="fas fa-trash-can" style="font-size: 0.8rem;"></i>
            </button>
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
