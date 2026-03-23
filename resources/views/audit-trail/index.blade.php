@extends('layouts.app')

@section('header_title', 'Audit Trails')

@section('content')
<div class="animate-fade">
    <div class="header-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div class="tab-pill-container" style="display: flex; background: #f1f5f9; padding: 5px; border-radius: 12px; gap: 5px;">
            <a href="{{ route('users.index') }}" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-users"></i> USERS
            </a>
            <a href="#" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; transition: all 0.2s; display: flex; align-items: center; gap: 8px; opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-user-tag"></i> ROLES
            </a>
            <a href="{{ route('audit-trail.index') }}" 
               class="tab-pill-item active" 
               style="padding: 10px 20px; text-decoration: none; color: #1e293b; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-history"></i> AUDIT LOGS
            </a>
        </div>

        <div style="margin-bottom: 8px;">
            <form id="filterForm" method="GET" action="{{ route('audit-trail.index') }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 0.85rem;"></i>
                    <input type="text" id="searchInput" name="search" value="{{ request('search') }}" 
                           placeholder="Search logs or user..." 
                           style="padding: 8px 12px 8px 35px; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 0.85rem; width: 230px; outline: none; (transition: border-color 0.2s);" oninput="debouncedFilter()">
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

    <div class="card" style="padding: 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; background: #fff; display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; background: #fef2f2; color: #991b1b; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-history"></i>
            </div>
            <h4 style="margin: 0; font-weight: 700; color: #1e293b; font-size: 1.1rem;">Activity Logs</h4>
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
