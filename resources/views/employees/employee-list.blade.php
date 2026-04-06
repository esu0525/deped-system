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
                <button type="button" class="btn btn-primary" onclick="openCreateModal('{{ route('employees.create', ['category' => 'employee']) }}')" title="Add Employee">
                    <i class="fas fa-user-plus"></i> Add Employee
                </button>
            </div>
        </div>

        <!-- Filters -->
        <form id="filterForm" action="{{ route('employees.index') }}" method="GET" target="_self" style="display: grid; grid-template-columns: 2fr 150px 150px 150px; gap: 15px; margin-bottom: 25px; background: rgba(59, 130, 246, 0.05); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
            <input type="hidden" name="export" id="exportFlag" value="">
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search name, school, or position..." value="{{ request('search') }}" oninput="debouncedFilter()">
            </div>
            <div class="form-group" style="margin-bottom: 0;">   
                <select name="status" class="form-control" onchange="fetchTable()">
                    <option value="">Status</option>
                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">   
                <select name="sort" class="form-control" onchange="fetchTable()">
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Sort: Name</option>
                    <option value="National" {{ request('sort') == 'National' ? 'selected' : '' }}>Sort: National</option>
                    <option value="City" {{ request('sort') == 'City' ? 'selected' : '' }}>Sort: City</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #f1f5f9; color: var(--secondary);">
                        <th style="padding: 15px; font-size: 0.75rem;">NAME</th>
                        <th style="padding: 15px; font-size: 0.75rem;">CATEGORY</th>
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
            {{ isset($employees) ? $employees->links('vendor.pagination.custom') : '' }}
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
            <h5 class="modal-title" id="createModalTitle">Add New Employee</h5>
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
    function submitEditForm(event) {
        event.preventDefault();
        const form = event.target;
        const btn = document.getElementById('editSubmitBtn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        const formData = new FormData(form);
        const url = "{{ route('employees.update', ':id') }}".replace(':id', form.dataset.id);

        fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": '{{ csrf_token() }}',
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error saving changes');
            return data;
        })
        .then(data => {
            Swal.fire({ icon: 'success', title: 'Updated!', text: 'Employee profile updated.', timer: 1500, showConfirmButton: false });
            closeEditModal();
            fetchTable();
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Failed', text: err.message });
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }

    function deleteUser(userId, userName) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete the user account for ${userName}. This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/users/${userId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success');
                        fetchTable(); // Refresh table
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error!', 'An unexpected error occurred.', 'error');
                });
            }
        });
    }
</script>
@endpush
@endsection

