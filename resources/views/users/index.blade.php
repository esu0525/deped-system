@extends('layouts.app')

@section('content')
<div class="animate-fade">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="font-weight: 900; color: var(--text-main); margin: 0; font-size: 2.5rem; letter-spacing: -1px;">Account Management</h2>
            <p style="color: var(--secondary); font-size: 1rem; margin-top: 5px; font-weight: 500;">Manage user accounts and access permissions</p>
        </div>
        @if(auth()->user()->role !== 'ojt')
        <button type="button" class="btn-add-account" onclick="openCreateUserModal()">
            <i class="fas fa-plus"></i> Add User
        </button>
        @endif
    </div>

    <!-- Search / Filter Area -->
    <div class="card glass animate-fade" style="padding: 20px; border-radius: 15px; margin-bottom: 25px; border: 2px solid rgba(255, 255, 255, 0.8);">
        <div style="position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input type="text" id="userSearchInput" class="form-control" placeholder="Search by name, email, or role..." 
            style="padding-left: 45px; height: 50px;" oninput="debouncedFilterUsers()">
        </div>
    </div>
    
    <!-- Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <button type="button" id="tab-active" class="btn-tab active" onclick="setStatusFilter('Active')">
            <i class="fas fa-user-check" style="margin-right: 8px;"></i> Active Users
        </button>
        @if(auth()->user()->role !== 'ojt')
        <button type="button" id="tab-inactive" class="btn-tab" onclick="setStatusFilter('Inactive')">
            <i class="fas fa-user-slash" style="margin-right: 8px;"></i> Inactive Users
        </button>
        @endif
        <input type="hidden" id="currentStatusFilter" value="Active">
    </div>

    <!-- Accounts Table Card -->
    <div class="card" style="border-radius: 20px; overflow: hidden;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase; width: 30%;">User Detail</th>
                        <th style="padding: 15px; text-align: center; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase;">Role</th>
                        <th style="padding: 15px; text-align: center; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase;">Permissions</th>
                        <th style="padding: 15px; text-align: center; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase;">Status</th>
                        <th style="padding: 15px; text-align: center; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase;">Last Logged In</th>
                        <th style="padding: 15px; text-align: center; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase;">Created</th>
                        <th style="padding: 15px 25px; text-align: right; font-size: 0.75rem; font-weight: 800; color: var(--secondary); letter-spacing: 1px; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @include('users.partials.user-table-rows')
                </tbody>
            </table>
        </div>
    </div>

    <div id="userPagination" style="margin-top: 25px;">
        {{ $users->links('vendor.pagination.custom') }}
    </div>
</div>

<!-- Simple Add User Modal (Glassmorphism Styled) -->
<div class="modal-overlay" id="createUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(10px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="modal-container" style="background: var(--bg-card); width: 100%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; border-radius: 25px; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.3); border: 1px solid var(--border-color); overflow: hidden;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-body); flex-shrink: 0;">
            <h5 style="margin: 0; font-weight: 900; color: var(--text-main); font-size: 1.25rem;">Create New Account</h5>
            <button type="button" onclick="closeCreateUserModal()" style="border: none; background: none; font-size: 1.5rem; color: var(--secondary); cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form id="createUserForm" onsubmit="submitCreateUser(event)" style="padding: 30px; overflow-y: auto;">
            @csrf
            <div style="display: grid; gap: 15px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">First Name</label>
                        <input type="text" name="first_name" class="form-control" placeholder="John" required style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem; transition: all 0.2s;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" placeholder="M." style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 80px; gap: 12px;">
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Last Name</label>
                        <input type="text" name="last_name" class="form-control" placeholder="Doe" required style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Suffix</label>
                        <input type="text" name="suffix" class="form-control" placeholder="Jr." style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com" required style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Default Role</label>
                        <select name="role" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                            @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                            <option value="admin">System Admin</option>
                            @endif
                            <option value="coordinator">Coordinator</option>
                            <option value="ojt">OJT Trainee</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Assign To</label>
                        <select name="assign" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                            <option value="National">National</option>
                            <option value="City">City</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="position: relative;">
                    <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Positions (Select Multiple)</label>
                    <div class="custom-dropdown" style="border: 1.5px solid var(--border-color); border-radius: 10px; background: var(--bg-body); cursor: pointer;">
                        <div id="dropdownHeaderIndex" class="dropdown-header" onclick="toggleDropdown('permissionsDropdownIndex')" style="height: 40px; display: flex; align-items: center; justify-content: space-between; padding: 0 12px; font-weight: 600; color: var(--text-main); font-size: 0.85rem;">
                            <span>Select Positions...</span>
                            <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                        </div>
                        <div class="dropdown-body" id="permissionsDropdownIndex" style="display: none; position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: var(--bg-card); border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); border: 1.5px solid var(--border-color); z-index: 100;">
                            <div style="padding: 10px; border-bottom: 1px solid var(--border-color); display: flex; gap: 8px;">
                                <input type="text" placeholder="Search position..." class="form-control" onkeyup="filterDropdown(this, 'permissionsListIndex')" style="border-radius: 6px; height: 32px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-size: 0.8rem; padding-left: 10px; flex: 1;">
                                @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                <button type="button" onclick="addNewPosition('permissionsListIndex')" style="background: #10b981; color: white; border: none; border-radius: 6px; padding: 0 10px; font-size: 0.75rem; font-weight: 800; cursor: pointer; white-space: nowrap;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                                @endif
                            </div>
                            <div id="permissionsListIndex" style="max-height: 180px; overflow-y: auto; padding: 8px;">
                                @foreach($rolesList as $rListItem)
                                <label class="dropdown-item-label" style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600; color: var(--text-main); cursor: pointer; padding: 6px 10px; border-radius: 6px; transition: 0.2s;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" name="access[]" value="{{ $rListItem }}" style="width: 14px; height: 14px; accent-color: #6366f1;" onchange="updateDropdownText('permissionsListIndex', 'dropdownHeaderIndex')">
                                    <span class="item-text">{{ $rListItem }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="createPasswordInput" name="password" class="form-control" required minlength="4" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); background: var(--bg-body); color: var(--text-main); padding-right: 40px; font-size: 0.9rem;">
                        <button type="button" onclick="togglePasswordVisibility('createPasswordInput', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: none; color: var(--secondary); cursor: pointer; padding: 0;">
                            <i class="fas fa-eye" style="font-size: 0.85rem;"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeCreateUserModal()" class="btn btn-secondary" style="border-radius: 10px; font-weight: 800; padding: 12px 25px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none; border-radius: 10px; font-weight: 800; padding: 12px 25px; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);">Create Account</button>
            </div>
        </form>
    </div>
</div>

<style>
    .btn-add-account {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: white; border: none; padding: 14px 30px; border-radius: 15px; font-weight: 900; font-size: 0.95rem; cursor: pointer;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-add-account:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4); }
    .swal2-backdrop-show { backdrop-filter: blur(12px) !important; background: rgba(15, 23, 42, 0.3) !important; }
    .swal2-popup { border-radius: 25px !important; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4) !important; border: none !important; }
    
    .btn-tab {
        padding: 12px 25px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
        background: var(--bg-card);
        color: var(--secondary);
    }
    .btn-tab.active {
        background: rgba(99, 102, 241, 0.1);
        border-color: #6366f1;
        color: #6366f1;
    }
    .btn-tab:hover:not(.active) {
        background: var(--hover-color);
        color: var(--text-main);
    }
</style>

@push('scripts')
<script>
    function openCreateUserModal() { document.getElementById('createUserModal').style.display = 'flex'; }
    function closeCreateUserModal() { document.getElementById('createUserModal').style.display = 'none'; }
    
    async function submitCreateUser(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const resp = await fetch("{{ route('users.store') }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Accept": "application/json" },
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                Swal.fire({ title: 'Success!', text: 'Account created!', icon: 'success' });
                closeCreateUserModal(); fetchUsers();
            } else {
                Swal.fire('Error!', data.message || 'Check fields', 'error');
            }
        } catch (error) { Swal.fire('Error!', 'System error', 'error'); }
    }

    async function fetchUsers() {
        const search = document.getElementById('userSearchInput').value;
        const status = document.getElementById('currentStatusFilter').value;
        const resp = await fetch(`{{ route('users.index') }}?search=${search}&status=${status}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        document.getElementById('userTableBody').innerHTML = await resp.text();
    }

    function setStatusFilter(status) {
        document.getElementById('currentStatusFilter').value = status;
        document.getElementById('tab-active').classList.toggle('active', status === 'Active');
        document.getElementById('tab-inactive').classList.toggle('active', status === 'Inactive');
        fetchUsers();
    }

    function debouncedFilterUsers() { clearTimeout(window.searchTimer); window.searchTimer = setTimeout(fetchUsers, 500); }

    function toggleUserStatus(id, action) {
        const title = action === 'activate' ? 'Activate Account?' : 'Deactivate Account?';
        const text = action === 'activate' ? 'This user will be able to log in again.' : 'This user will be prevented from logging in.';
        const confirmBtn = action === 'activate' ? '#10b981' : '#f59e0b';

        Swal.fire({
            title: title, text: text, icon: 'question', showCancelButton: true,
            confirmButtonColor: confirmBtn, confirmButtonText: `Yes, ${action}!`,
            backdrop: true
        }).then(async (result) => {
            if (result.isConfirmed) {
                const resp = await fetch(`/users/${id}/${action}`, {
                    method: "POST",
                    headers: { 
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 
                        "Accept": "application/json"
                    }
                });
                const data = await resp.json();
                if (data.success) { 
                    Swal.fire('Success!', data.message, 'success'); 
                    fetchUsers(); 
                } else { 
                    Swal.fire('Error!', data.message, 'error'); 
                }
            }
        });
    }

    function deleteUser(id, name) {
        Swal.fire({
            title: 'Are you sure?', text: `Remove ${name} from the system?`, icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it!',
            backdrop: true
        }).then(async (result) => {
            if (result.isConfirmed) {
                const resp = await fetch(`/users/${id}`, {
                    method: "POST",
                    headers: { 
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 
                        "Accept": "application/json", "Content-Type": "application/json" 
                    },
                    body: JSON.stringify({ _method: "DELETE" })
                });
                const data = await resp.json();
                if (data.success) { Swal.fire('Deleted!', 'User removed.', 'success'); fetchUsers(); }
                else { Swal.fire('Error!', data.message, 'error'); }
            }
        });
    }

    function viewUser(userId) {
        window.location.href = `/users/${userId}`;
    }

    // Dropdown functions
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
        
        // Ensure modal scrolls to it if needed
        if(el.style.display === 'block') {
            const form = document.getElementById('createUserForm');
            if(form) form.scrollTop = form.scrollHeight;
        }
    }
    
    function filterDropdown(input, listId) {
        const filter = input.value.toLowerCase();
        const labels = document.getElementById(listId).getElementsByClassName('dropdown-item-label');
        for (let i = 0; i < labels.length; i++) {
            const text = labels[i].getElementsByClassName('item-text')[0].innerText.toLowerCase();
            labels[i].style.display = text.includes(filter) ? 'flex' : 'none';
        }
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.dropdown-body').forEach(el => el.style.display = 'none');
        }
    });

    function updateDropdownText(listId, headerId) {
        const checked = document.getElementById(listId).querySelectorAll('input[type="checkbox"]:checked');
        const header = document.querySelector(`#${headerId} span`);
        if(checked.length > 0) {
            header.innerText = `${checked.length} selected`;
            header.style.color = '#6366f1';
            header.style.fontWeight = '800';
        } else {
            header.innerText = 'Select Positions...';
            header.style.color = '#475569';
            header.style.fontWeight = '600';
        }
    }

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    async function addNewPosition(listId) {
        const { value: posName } = await Swal.fire({
            title: 'Add New Position',
            input: 'text',
            inputLabel: 'Position Name',
            inputPlaceholder: 'Enter position name...',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            inputValidator: (value) => {
                if (!value) return 'You need to write something!'
            }
        });

        if (posName) {
            try {
                const resp = await fetch("{{ route('users.positions.store') }}", {
                    method: "POST",
                    headers: { 
                        "X-CSRF-TOKEN": "{{ csrf_token() }}", 
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ name: posName })
                });
                const data = await resp.json();
                if (data.success) {
                    Swal.fire('Added!', 'New position has been listed.', 'success');
                    // Add to the list visually
                    const list = document.getElementById(listId);
                    const label = document.createElement('label');
                    label.className = 'dropdown-item-label';
                    label.style = "display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600; color: var(--text-main); cursor: pointer; padding: 6px 10px; border-radius: 6px; transition: 0.2s;";
                    label.innerHTML = `<input type="checkbox" name="access[]" value="${posName}" style="width: 14px; height: 14px; accent-color: #6366f1;" onchange="updateDropdownText('${listId}', '${listId.replace('permissionsList', 'dropdownHeader')}')">
                                     <span class="item-text">${posName}</span>`;
                    list.appendChild(label);
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error!', 'Failed to save position.', 'error');
            }
        }
    }
</script>
@endpush
@endsection
