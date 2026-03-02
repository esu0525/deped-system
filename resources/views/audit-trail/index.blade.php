@extends('layouts.app')

@section('header_title', 'Audit Trails')

@section('content')
<div class="animate-fade">
    <div class="card glass">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px;">
            <h4 style="font-weight: 700; margin-bottom: 0;"><i class="fas fa-clock-rotate-left text-primary"></i> Activity Logs</h4>
            
            <form id="filterForm" method="GET" action="{{ route('audit-trail.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <div style="position: relative;">
                    <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search logs or user..." value="{{ request('search') }}" style="width: 280px; padding-left: 35px;" oninput="debouncedFilter()">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--secondary); opacity: 0.5;"></i>
                </div>
                
                <select name="module" class="form-control" style="width: 180px;" onchange="fetchTable()">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}">{{ $module }}</option>
                    @endforeach
                </select>

                <select name="action" class="form-control" style="width: 140px;" onchange="fetchTable()">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div style="overflow-x: auto; position: relative;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #f1f5f9; color: var(--secondary);">
                        <th style="padding: 15px; font-size: 0.75rem;">DATE/TIME</th>
                        <th style="padding: 15px; font-size: 0.75rem;">USER</th>
                        <th style="padding: 15px; font-size: 0.75rem;">ACTION</th>
                        <th style="padding: 15px; font-size: 0.75rem;">MODULE</th>
                        <th style="padding: 15px; font-size: 0.75rem;">DESCRIPTION</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody">
                    @include('audit-trail.partials.table-rows')
                </tbody>
            </table>
        </div>

        <div id="paginationContainer" style="margin-top: 25px; background: #f8fafc; padding: 15px 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px;">
                SHOWING {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} OF {{ $logs->total() }} ENTRIES
            </div>
            <div style="display: flex; gap: 10px;">
                @if($logs->onFirstPage())
                    <span class="btn-pagination disabled"><i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="btn-pagination" title="Previous Page"><i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous</a>
                @endif

                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="btn-pagination active" title="Next Page">Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a>
                @else
                    <span class="btn-pagination disabled active">Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></span>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let filterTimer;
    let fetchController = null;

    function debouncedFilter() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            fetchTable();
        }, 300);
    }

    function fetchTable(url = null) {
        const form = document.getElementById('filterForm');
        const tableBody = document.getElementById('auditTableBody');
        const paginationContainer = document.getElementById('paginationContainer');

        if (fetchController) fetchController.abort();
        fetchController = new AbortController();

        if (!url) {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            url = `${form.action}?${params.toString()}`;
        }

        tableBody.style.opacity = '0.6';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: fetchController.signal
        })
        .then(res => res.text())
        .then(html => {
            const tempTable = document.createElement('table');
            const tempTbody = document.createElement('tbody');
            tempTable.appendChild(tempTbody);
            tempTbody.innerHTML = html;
            
            const paginationRow = tempTbody.querySelector('#paginationLinksContainer');
            if (paginationRow) {
                paginationContainer.innerHTML = paginationRow.querySelector('td').innerHTML;
                paginationRow.remove();
            }
            
            tableBody.innerHTML = tempTbody.innerHTML;
            tableBody.style.opacity = '1';
            history.pushState(null, '', url);
        })
        .catch(err => {
            if (err.name !== 'AbortError') console.error('Audit Fetch Error:', err);
            tableBody.style.opacity = '1';
        });
    }

    // Pagination interception
    document.getElementById('paginationContainer').addEventListener('click', (e) => {
        if (e.target.closest('a')) {
            e.preventDefault();
            fetchTable(e.target.closest('a').href);
        }
    });

    window.onload = () => {
        const searchInput = document.getElementById('searchInput');
        if (searchInput && searchInput.value) {
            searchInput.focus();
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }
    };
</script>
@endpush
@endsection
