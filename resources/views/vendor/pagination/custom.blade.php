@if ($paginator->hasPages())
<div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px;">
    {{-- Showing X-Y of Z entries --}}
    <div style="font-size: 0.78rem; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px;">
        Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
    </div>

    {{-- Previous / Next buttons --}}
    <div style="display: flex; gap: 10px; align-items: center;">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="btn-pagination disabled" style="padding: 8px 20px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; border: 1px solid #e2e8f0; background: #fff; color: #cbd5e1; cursor: not-allowed; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding: 8px 20px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; border: 1px solid #e2e8f0; background: #fff; color: var(--secondary); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                <i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous
            </a>
        @endif

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding: 8px 20px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; border: none; background: linear-gradient(135deg, #7c3aed, #6366f1); color: #fff; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 16px rgba(99,102,241,0.35)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 12px rgba(99,102,241,0.3)'">
                Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
            </a>
        @else
            <span style="padding: 8px 20px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; border: none; background: linear-gradient(135deg, #7c3aed, #6366f1); color: #fff; cursor: not-allowed; display: inline-flex; align-items: center; gap: 6px; opacity: 0.5;">
                Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
            </span>
        @endif
    </div>
</div>
@endif
