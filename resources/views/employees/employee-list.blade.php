@extends('layouts.app')

@section('header_title', 'Employee Management')

@section('content')
<div class="animate-fade">
    <div class="card glass animate-fade">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px;">
            <h4 style="font-weight: 700;">Employee Management</h4>
            <div style="display: flex; gap: 12px; align-items: center;">
                <button type="button" class="btn btn-secondary" title="Export List" onclick="document.getElementById('exportFlag').value='true'; document.getElementById('filterForm').submit(); document.getElementById('exportFlag').value='';">
                    <i class="fas fa-file-export"></i> Export
                </button>
                <button type="button" class="btn btn-primary" onclick="openCreateModal('{{ route('employees.create') }}')" title="Add User">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div style="display: flex; gap: 4px; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 0;">
            <a href="{{ route('employees.index', ['type' => 'employee']) }}" 
               class="tab-btn {{ !request('type') || request('type') == 'employee' ? 'active' : '' }}" 
               onclick="switchTab(event, 'employee')"
               id="tab-employee"
               style="padding: 12px 24px; font-weight: 700; font-size: 0.9rem; position: relative; color: {{ !request('type') || request('type') == 'employee' ? 'var(--primary)' : 'var(--secondary)' }}; cursor: pointer; text-decoration: none;">
               Employees
               <div class="tab-indicator" style="position: absolute; bottom: -2px; left: 0; right: 0; height: 2px; background: var(--primary); display: {{ !request('type') || request('type') == 'employee' ? 'block' : 'none' }};"></div>
            </a>
            <a href="{{ route('employees.index', ['type' => 'ntp']) }}" 
               class="tab-btn {{ request('type') == 'ntp' ? 'active' : '' }}" 
               onclick="switchTab(event, 'ntp')"
               id="tab-ntp"
               style="padding: 12px 24px; font-weight: 700; font-size: 0.9rem; position: relative; color: {{ request('type') == 'ntp' ? 'var(--primary)' : 'var(--secondary)' }}; cursor: pointer; text-decoration: none;">
               NTP
               <div class="tab-indicator" style="position: absolute; bottom: -2px; left: 0; right: 0; height: 2px; background: var(--primary); display: {{ request('type') == 'ntp' ? 'block' : 'none' }};"></div>
            </a>
        </div>

        <!-- Filters -->
        <form id="filterForm" action="{{ route('employees.index') }}" method="GET" target="_self" style="display: grid; grid-template-columns: 2fr 1fr 150px; gap: 15px; margin-bottom: 25px; background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="type" id="typeInput" value="{{ request('type', 'employee') }}">
            <input type="hidden" name="export" id="exportFlag" value="">
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search name, school, or position..." value="{{ request('search') }}" oninput="debouncedFilter()">
            </div>
            <div class="form-group" style="margin-bottom: 0;">   
                <select name="status" class="form-control" onchange="fetchTable()">
                    <option value="">All Status</option>
                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" style="flex: 1; background: #166534; border-color: #166534;" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i> Import List
                </button>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #f1f5f9; color: var(--secondary);">
                        <th style="padding: 15px; font-size: 0.75rem;">NAME</th>
                        <th style="padding: 15px; font-size: 0.75rem;">SCHOOL</th>
                        <th style="padding: 15px; font-size: 0.75rem;">POSITION</th>
                        <th style="padding: 15px; font-size: 0.75rem;">STATUS</th>
                        <th style="padding: 15px; font-size: 0.75rem; text-align: center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    @include('employees.partials.employee-table-rows')
                </tbody>
            </table>
        </div>

        <div id="paginationContainer" style="margin-top: 25px;">
            {{ $employees->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal-overlay" id="viewModalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h5 class="modal-title">Employee Profile</h5>
            <button type="button" class="modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
        </div>
        <div id="viewModalContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h5 class="modal-title">Edit Employee Information</h5>
            <button type="button" class="modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div id="editModalContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<div class="modal-overlay" id="createModalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h5 class="modal-title">Add New Employee</h5>
            <button type="button" class="modal-close" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
        </div>
        <div id="createModalContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<div class="modal-overlay" id="importModalOverlay">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h5 class="modal-title">Import Employees</h5>
            <button type="button" class="modal-close" onclick="closeImportModal()"><i class="fas fa-times"></i></button>
        </div>
        <div id="importModalContent">
            @include('employees.partials.import-modal')
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
        }, 200);
    }

    function fetchTable(url = null) {
        const form = document.getElementById('filterForm');
        const tableBody = document.getElementById('employeeTableBody');
        const paginationContainer = document.getElementById('paginationContainer');

        // Cancel previous request if it's still running
        if (fetchController) {
            fetchController.abort();
        }
        fetchController = new AbortController();

        if (!url) {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            url = `${form.action}?${params.toString()}`;
        }

        tableBody.style.opacity = '0.7';

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
            if (err.name !== 'AbortError') {
                console.error('Fetch Error:', err);
            }
        });
    }

    // Intercept pagination clicks
    document.getElementById('paginationContainer').addEventListener('click', (e) => {
        if (e.target.closest('a')) {
            e.preventDefault();
            fetchTable(e.target.closest('a').href);
        }
    });

    function openViewModal(url) {
        const overlay = document.getElementById('viewModalOverlay');
        const content = document.getElementById('viewModalContent');
        overlay.classList.add('active');
        content.innerHTML = '<div class="modal-skeleton"><div class="skeleton-bar" style="width: 50%"></div><div class="skeleton-bar"></div><div class="skeleton-bar" style="width: 80%"></div></div>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text()).then(html => content.innerHTML = html);
    }
    function closeViewModal() { document.getElementById('viewModalOverlay').classList.remove('active'); }

    function openEditModal(url) {
        const overlay = document.getElementById('editModalOverlay');
        const content = document.getElementById('editModalContent');
        overlay.classList.add('active');
        content.innerHTML = '<div class="modal-skeleton"><div class="skeleton-bar" style="width: 50%"></div><div class="skeleton-bar"></div><div class="skeleton-bar" style="width: 80%"></div></div>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text()).then(html => content.innerHTML = html);
    }
    function closeEditModal() { document.getElementById('editModalOverlay').classList.remove('active'); }

    function openCreateModal(url) {
        const overlay = document.getElementById('createModalOverlay');
        const content = document.getElementById('createModalContent');
        overlay.classList.add('active');
        content.innerHTML = '<div class="modal-skeleton"><div class="skeleton-bar" style="width: 50%"></div><div class="skeleton-bar"></div><div class="skeleton-bar" style="width: 80%"></div></div>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text()).then(html => content.innerHTML = html);
    }
    function closeCreateModal() { document.getElementById('createModalOverlay').classList.remove('active'); }

    function openImportModal() { document.getElementById('importModalOverlay').classList.add('active'); }
    function closeImportModal() { document.getElementById('importModalOverlay').classList.remove('active'); }




    // Modal Close events
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('active');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
        }
    });

    // Move cursor to end of search input if it has value
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

