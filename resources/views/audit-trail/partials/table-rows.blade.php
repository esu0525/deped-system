@forelse($logs as $log)
<tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;">
    <td style="padding: 15px; font-size: 0.85rem; color: var(--secondary);">
        {{ $log->created_at->format('M d, Y h:i A') }}
    </td>
    <td style="padding: 15px;">
        <p style="font-weight: 600; margin: 0; color: var(--dark);">{{ $log->user->name ?? 'System' }}</p>
        @if($log->user?->employee) <small style="color: var(--secondary);">ID: {{ $log->user->employee->employee_id }}</small> @endif
    </td>
    <td style="padding: 15px;">
        @php
            $actionColor = match($log->action) {
                'CREATE' => '#10b981',
                'UPDATE' => '#3b82f6',
                'DELETE' => '#ef4444',
                'LOGIN' => '#8b5cf6',
                'LOGOUT' => '#64748b',
                default => '#3b82f6'
            };
            $actionBg = match($log->action) {
                'CREATE' => 'rgba(16, 185, 129, 0.1)',
                'UPDATE' => 'rgba(59, 130, 246, 0.1)',
                'DELETE' => 'rgba(239, 68, 68, 0.1)',
                'LOGIN' => 'rgba(139, 92, 246, 0.1)',
                'LOGOUT' => 'rgba(100, 116, 139, 0.1)',
                default => 'rgba(59, 130, 246, 0.1)'
            };
        @endphp
        <span class="badge" style="background: {{ $actionBg }}; color: {{ $actionColor }}; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem;">
            {{ $log->action }}
        </span>
    </td>
    <td style="padding: 15px; font-size: 0.85rem;">{{ $log->module }}</td>
    <td style="padding: 15px; max-width: 400px;">
        <p style="font-size: 0.8rem; line-height: 1.4; color: var(--dark); margin: 0;">{{ $log->description }}</p>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" style="padding: 40px; text-align: center; color: var(--secondary);">
        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2;"></i>
        <p style="font-weight: 600;">No activity logs found matching your search.</p>
    </td>
</tr>
@endforelse

<!-- Pagination Link Update Identifier -->
<tr id="paginationLinksContainer" style="display: none;">
    <td>
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px;">
                SHOWING {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} OF {{ $logs->total() }} ENTRIES
            </div>
            <div style="display: flex; gap: 10px;">
                @if($logs->onFirstPage())
                    <span class="btn-pagination disabled"><i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="btn-pagination"><i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous</a>
                @endif

                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="btn-pagination active">Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a>
                @else
                    <span class="btn-pagination disabled active">Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></span>
                @endif
            </div>
        </div>
    </td>
</tr>
