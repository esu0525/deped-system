@extends('layouts.app')

@section('header_title', 'Audit Trails')

@section('content')
<div class="animate-fade">
    <div class="header-container" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 25px;">
        <div style="margin-bottom: 8px;">
            <form id="filterForm" method="GET" action="{{ route('audit-trail.index') }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 0.85rem;"></i>
                    <input type="text" id="searchInput" name="search" value="{{ request('search') }}" 
                           placeholder="Search logs or user..." 
                           style="padding: 8px 12px 8px 35px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 20px; font-size: 0.85rem; width: 230px; outline: none; color: var(--text-main);" oninput="debouncedFilter()">
                </div>
                
                <select name="module" class="form-control" style="width: 150px; font-size: 0.8rem; border-radius: 8px;" onchange="fetchTable()">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}">{{ $module }}</option>
                    @endforeach
                </select>

                <select name="action" class="form-control" style="width: 130px; font-size: 0.8rem; border-radius: 8px;" onchange="fetchTable()">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="card" style="padding: 0; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: var(--bg-card); box-shadow: var(--card-shadow);">
        <div style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: var(--bg-card); display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-history"></i>
            </div>
            <h4 style="margin: 0; font-weight: 700; color: var(--text-main); font-size: 1.1rem;">Activity Logs</h4>
        </div>

        <div style="overflow-x: auto; position: relative;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--secondary);">
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

        <div id="paginationContainer" style="margin-top: 25px; background: var(--bg-body); padding: 15px 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.8px;">
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
            url = `${form.getAttribute('action')}?${params.toString()}`;
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
