@extends('layouts.app')

@section('header_title', 'User Management')

@section('content')
<div class="animate-fade">
    <div class="header-container" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 25px;">
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
                <tbody id="userTableBody">
                    @include('users.partials.user-table-rows')
                </tbody>
            </table>
        </div>

        <div id="paginationContainer" style="padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #f8fafc;">
            @if($users->hasPages())
                {{ $users->links('vendor.pagination.custom') }}
            @endif
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
        const searchInput = document.querySelector('input[name="search"]');
        const tableBody = document.getElementById('userTableBody');
        const paginationContainer = document.getElementById('paginationContainer');

        if (fetchController) {
            fetchController.abort();
        }
        fetchController = new AbortController();

        if (!url) {
            const params = new URLSearchParams({ search: searchInput.value });
            url = `{{ route('users.index') }}?${params.toString()}`;
        }

        tableBody.style.opacity = '0.5';

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
            } else {
                paginationContainer.innerHTML = '';
            }
            
            tableBody.innerHTML = tempTbody.innerHTML;
            tableBody.style.opacity = '1';
            history.pushState(null, '', url);
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                console.error('Fetch Error:', err);
                tableBody.style.opacity = '1';
            }
        });
    }

    // Intercept search input
    document.querySelector('input[name="search"]').addEventListener('input', debouncedFilter);

    // Intercept pagination clicks
    document.getElementById('paginationContainer').addEventListener('click', (e) => {
        if (e.target.closest('a')) {
            e.preventDefault();
            fetchTable(e.target.closest('a').href);
        }
    });

    // Handle form submission to prevent page reload
    document.querySelector('form').addEventListener('submit', (e) => {
        e.preventDefault();
        fetchTable();
    });
</script>
@endpush
@endsection
