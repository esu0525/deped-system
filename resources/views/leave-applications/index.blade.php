@extends('layouts.app')

@section('header_title', 'Leave Applications')

@section('content')
    <div class="animate-fade">
        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
            <div class="card glass" style="text-align: center; border-bottom: 3px solid var(--primary);">
                <i class="fas fa-file-signature fa-2x" style="color: var(--primary); margin-bottom: 10px;"></i>
                <h2 style="font-weight: 800; color: var(--primary);">{{ $stats['total'] }}</h2>
                <small style="color: var(--secondary); font-weight: 600;">Total Applications</small>
            </div>
            <div class="card glass" style="text-align: center; border-bottom: 3px solid var(--warning);">
                <i class="fas fa-clock fa-2x" style="color: var(--warning); margin-bottom: 10px;"></i>
                <h2 style="font-weight: 800; color: var(--warning);">{{ $stats['pending'] }}</h2>
                <small style="color: var(--secondary); font-weight: 600;">Pending</small>
            </div>
            <div class="card glass" style="text-align: center; border-bottom: 3px solid var(--success);">
                <i class="fas fa-check-circle fa-2x" style="color: var(--success); margin-bottom: 10px;"></i>
                <h2 style="font-weight: 800; color: var(--success);">{{ $stats['approved'] }}</h2>
                <small style="color: var(--secondary); font-weight: 600;">Approved</small>
            </div>
            <div class="card glass" style="text-align: center; border-bottom: 3px solid var(--danger);">
                <i class="fas fa-times-circle fa-2x" style="color: var(--danger); margin-bottom: 10px;"></i>
                <h2 style="font-weight: 800; color: var(--danger);">{{ $stats['rejected'] }}</h2>
                <small style="color: var(--secondary); font-weight: 600;">Rejected</small>
            </div>
        </div>

        <!-- Tabs & Filters -->
        <div class="card glass animate-fade" style="margin-bottom: 24px; padding: 10px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; gap: 25px;">
                    @php $currentStatus = request('status', 'Pending'); @endphp
                    <button type="button" class="status-tab {{ $currentStatus === 'All' ? 'active' : '' }}"
                        onclick="switchStatus('All', this)">All</button>
                    <button type="button" class="status-tab {{ $currentStatus === 'Pending' ? 'active' : '' }}"
                        onclick="switchStatus('Pending', this)">Pending</button>
                    <button type="button" class="status-tab {{ $currentStatus === 'Approved' ? 'active' : '' }}"
                        onclick="switchStatus('Approved', this)">Approved</button>
                    <button type="button" class="status-tab {{ $currentStatus === 'Rejected' ? 'active' : '' }}"
                        onclick="switchStatus('Rejected', this)">Rejected</button>
                </div>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <form id="filterForm" method="GET" action="{{ route('leave-applications.index') }}"
                        style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="status" id="statusFilter" value="{{ request('status', 'Pending') }}">
                        <div style="position: relative;">
                            <input type="text" id="searchInput" name="search" class="form-control"
                                placeholder="Search employee..." value="{{ request('search') }}"
                                style="width: 240px; padding-left: 35px;" oninput="debouncedFilter()">
                            <i class="fas fa-search"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--secondary); opacity: 0.5;"></i>
                        </div>
                        <select name="leave_type" class="form-control" style="width: 200px;" onchange="fetchTable()">
                            <option value="">All Leave Types</option>
                            @foreach($leaveTypes as $lt)
                                <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if(auth()->user()->canApproveLeave())
                        <button type="button" id="bulkApproveBtn" class="btn btn-success"
                            style="height: 42px; display: none; align-items: center; gap: 8px;" onclick="submitBulkApprove()">
                            <i class="fas fa-check-double"></i> Bulk Approve
                        </button>
                    @endif
                    <a href="{{ route('leave-applications.create') }}" class="btn btn-primary"
                        style="height: 42px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus-circle"></i> New Application
                    </a>
                </div>
            </div>
        </div>

        <style>
            .status-tab {
                background: none;
                border: none;
                padding: 10px 5px;
                font-weight: 600;
                color: var(--secondary);
                cursor: pointer;
                position: relative;
                transition: all 0.3s ease;
            }

            .status-tab:hover {
                color: var(--primary);
            }

            .status-tab.active {
                color: var(--primary);
            }

            .status-tab.active::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: var(--primary);
                border-radius: 3px;
            }
        </style>

        <!-- Applications Table -->
        <div class="card glass animate-fade" style="position: relative;">
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #f1f5f9; color: var(--secondary);">
                            <th style="padding: 15px; width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                            </th>
                            <th style="padding: 15px; font-size: 0.75rem;">APP NO.</th>
                            <th style="padding: 15px; font-size: 0.75rem;">EMPLOYEE</th>
                            <th style="padding: 15px; font-size: 0.75rem;">LEAVE TYPE</th>
                            <th style="padding: 15px; font-size: 0.75rem;">DAYS</th>
                            <th style="padding: 15px; font-size: 0.75rem;">STATUS</th>
                            <th style="padding: 15px; font-size: 0.75rem;">FILED ON</th>
                            <th style="padding: 15px; font-size: 0.75rem;">ACTIONED BY</th>
                            <th style="padding: 15px; font-size: 0.75rem; text-align: center;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="applicationTableBody">
                        @include('leave-applications.partials.table-rows')
                    </tbody>
                </table>
            </div>

            <div id="paginationContainer"
                style="margin-top: 25px; background: #f8fafc; padding: 12px 20px; border-radius: 12px;">
                {{ $applications->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>

    <!-- View Modal Container -->
    <div class="modal-overlay" id="viewModalOverlay">
        <div class="modal-container">
            <div class="modal-header">
                <h5 class="modal-title">Leave Application Details</h5>
                <button type="button" class="modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="modalContent">
                <!-- Content loaded via AJAX -->
                <div class="modal-skeleton">
                    <div class="skeleton-bar" style="width: 40%"></div>
                    <div class="skeleton-bar"></div>
                    <div class="skeleton-bar" style="width: 80%"></div>
                    <div class="skeleton-bar" style="width: 90%; height: 100px; margin-top: 20px;"></div>
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
                }, 200);
            }

            function switchStatus(status, el) {
                document.getElementById('statusFilter').value = status;
                document.querySelectorAll('.status-tab').forEach(tab => tab.classList.remove('active'));
                el.classList.add('active');
                fetchTable();
            }

            function toggleSelectAll() {
                const isChecked = document.getElementById('selectAllCheckbox').checked;
                document.querySelectorAll('.app-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                });
                updateBulkApproveBtn();
            }

            function updateBulkApproveBtn() {
                const anyChecked = document.querySelectorAll('.app-checkbox:checked').length > 0;
                const btn = document.getElementById('bulkApproveBtn');
                if (btn) {
                    btn.style.display = anyChecked ? 'flex' : 'none';
                }
            }

            function submitBulkApprove() {
                const btn = document.getElementById('bulkApproveBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
                btn.disabled = true;

                const ids = Array.from(document.querySelectorAll('.app-checkbox:checked')).map(cb => cb.value);
                if (ids.length === 0) return;

                fetch("{{ route('leave-applications.bulk-approve') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ application_ids: ids })
                })
                    .then(res => res.json())
                    .then(data => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;

                        if (data.success) {
                            const hasFailures = data.message.includes('Failed:');
                            
                            Swal.fire({
                                title: hasFailures ? 'Bulk Approval Processed' : 'Success!',
                                html: data.message.replace('Failed:', '<br><strong style="color: #ef4444;">Failed:</strong>'),
                                icon: hasFailures ? 'info' : 'success',
                                confirmButtonColor: 'var(--primary)',
                                confirmButtonText: 'Got it'
                            }).then(() => {
                                fetchTable();
                                document.getElementById('selectAllCheckbox').checked = false;
                                updateBulkApproveBtn();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error processing bulk approval.',
                                icon: 'error',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        Swal.fire({
                            title: 'System Error',
                            text: 'An unexpected error occurred while processing your request.',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    });
            }

            function fetchTable(url = null) {
                const form = document.getElementById('filterForm');
                const tableBody = document.getElementById('applicationTableBody');
                const paginationContainer = document.getElementById('paginationContainer');

                if (fetchController) fetchController.abort();
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
                        if (err.name !== 'AbortError') console.error('Fetch Error:', err);
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
                const content = document.getElementById('modalContent');
                overlay.classList.add('active');
                content.innerHTML = `
                    <div class="modal-skeleton">
                        <div class="skeleton-bar" style="width: 40%"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar" style="width: 80%"></div>
                    </div>`;
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.text()).then(html => content.innerHTML = html);
            }

            function closeViewModal() {
                document.getElementById('viewModalOverlay').classList.remove('active');
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeViewModal();
            });

            document.getElementById('viewModalOverlay').addEventListener('click', (e) => {
                if (e.target.id === 'viewModalOverlay') closeViewModal();
            });

            window.confirmModalReject = function () {
                Swal.fire({
                    title: 'Reject Application',
                    input: 'textarea',
                    inputPlaceholder: 'Enter reason for rejection...',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Reject',
                    inputValidator: (value) => { if (!value) return 'Required!' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('modalRejectRemarks').value = result.value;
                        setTimeout(() => {
                            document.getElementById('modalRejectForm').submit();
                        }, 100);
                    }
                });
            };
        </script>
    @endpush
@endsection