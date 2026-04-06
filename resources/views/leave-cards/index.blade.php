@extends('layouts.app')

@section('header_title', 'Leave Ledger')

@section('content')
<div class="animate-fade">
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div class="card glass" style="padding: 20px; border-left: 4px solid var(--primary); display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(30, 41, 59, 0.05); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.5rem;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--secondary); font-weight: 600; text-transform: uppercase;">Total Employees</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--dark);">{{ number_format($stats['total'] ?? 0) }}</div>
            </div>
        </div>
        <div class="card glass" style="padding: 20px; border-left: 4px solid #3b82f6; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 1.5rem;">
                <i class="fas fa-building-flag"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--secondary); font-weight: 600; text-transform: uppercase;">National</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--dark);">{{ number_format($stats['national'] ?? 0) }}</div>
            </div>
        </div>
        <div class="card glass" style="padding: 20px; border-left: 4px solid #10b981; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.5rem;">
                <i class="fas fa-city"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--secondary); font-weight: 600; text-transform: uppercase;">City</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--dark);">{{ number_format($stats['city'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="card glass animate-fade" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <form id="filterForm" style="display: flex; gap: 12px; align-items: center;">
                <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search employee name or ID..." value="{{ request('search') }}" style="width: 300px;" oninput="debouncedFilter()">
                <select name="sort" class="form-control" style="width: 150px;" onchange="fetchTable()">
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Sort: Name</option>
                    <option value="National" {{ request('sort') == 'National' ? 'selected' : '' }}>Sort: National</option>
                    <option value="City" {{ request('sort') == 'City' ? 'selected' : '' }}>Sort: City</option>
                </select>
            </form>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="badge badge-info" style="font-size: 0.8rem;"><i class="fas fa-calendar"></i> Year {{ now()->year }}</span>
            </div>
        </div>
    </div>

    <!-- Employee Leave Cards Table -->
    <div class="card glass animate-fade">
        <h4 style="font-weight: 700; margin-bottom: 20px;"><i class="fas fa-address-card text-primary"></i> Employee Leave Credits</h4>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th style="text-align: center;">VL Balance</th>
                        <th style="text-align: center;">SL Balance</th>

                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @include('leave-cards.partials.leave-card-rows', ['employees' => $employees])
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $employees->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>

<script>
    let filterTimeout;
    
    function debouncedFilter() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            fetchTable();
        }, 300);
    }
    
    function fetchTable() {
        // Collect exact current parameters
        const form = document.getElementById('filterForm');
        if (!form) return;
        
        const params = new URLSearchParams();
        const inputs = form.querySelectorAll('input, select');
        
        inputs.forEach(input => {
            if (input.value.trim() !== '') {
                params.append(input.name, input.value.trim());
            }
        });
        
        const queryString = params.toString();
        const url = `${window.location.pathname}${queryString ? '?' + queryString : ''}`;

        // Add loading state to table container
        const tableBody = document.getElementById('tableBody');
        if (tableBody) {
            tableBody.style.opacity = '0.5';
            tableBody.style.pointerEvents = 'none';
        }

        // Push state without reloading to preserve browser history
        window.history.pushState({}, '', url);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.text();
        })
        .then(html => {
            if (tableBody) {
                tableBody.innerHTML = html;
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
        })
        .catch(error => {
            console.error('Error fetching table data:', error);
            if (tableBody) {
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
        });
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        window.location.reload();
    });
</script>
@endsection
