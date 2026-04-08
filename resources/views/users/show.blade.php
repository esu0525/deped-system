@extends('layouts.app')

@section('content')
<div class="animate-fade">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
        <a href="{{ route('users.index') }}" style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; color: var(--text-main); box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-decoration: none;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="font-weight: 900; color: var(--text-main); margin: 0; font-size: 2rem;">{{ explode(' ', $user->name)[0] }}'s Profile</h2>
            <p style="color: var(--secondary); font-size: 0.9rem; margin-top: 5px; font-weight: 500;">Manage account roles, permissions and security</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: start;">
        <!-- Left Sidebar -->
        <div style="display: grid; gap: 30px;">
            <div class="card" style="background: var(--bg-card); border-radius: 25px; border: none; box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1); overflow: hidden;">
                <div style="height: 120px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);"></div>
                <div style="padding: 0 30px 40px; text-align: center;">
                    <div style="position: relative; top: -50px; display: inline-block;">
                        <div style="width: 110px; height: 110px; border-radius: 35px; background: var(--bg-card); padding: 5px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                            @if($user->avatar)
                                <img id="avatarPreview" src="{{ asset('storage/' . $user->avatar) }}" style="width: 100%; height: 100%; border-radius: 30px; object-fit: cover;">
                            @else
                                <div id="avatarPlaceholder" style="width: 100%; height: 100%; border-radius: 30px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #6366f1; font-weight: 900; font-size: 2rem;">
                                    {{ strtoupper(collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('')) }}
                                </div>
                            @endif
                        </div>
                        <label for="avatarInput" style="position: absolute; bottom: 0; right: 0; width: 35px; height: 35px; border-radius: 50%; background: #1e293b; border: 3px solid white; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; transition: 0.2s;">
                            <i class="fas fa-camera" style="font-size: 0.85rem;"></i>
                        </label>
                    </div>
                    <div style="margin-top: -30px;">
                        <h3 style="font-weight: 900; color: var(--text-main); margin: 0; font-size: 1.5rem;">{{ $user->name }}</h3>
                        <span style="display: inline-block; padding: 4px 12px; background: var(--bg-body); border-radius: 8px; font-size: 0.75rem; color: var(--secondary); font-weight: 800; text-transform: uppercase; margin-top: 10px; border: 1px solid var(--border-color);">
                            {{ $user->role }}
                        </span>
                        <p style="color: var(--secondary); font-size: 0.8rem; font-weight: 600; margin-top: 20px;">Joined {{ $user->created_at->format('F d, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card" style="background: var(--bg-card); border-radius: 25px; border: none; box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1); padding: 30px;">
                <h4 style="margin: 0 0 20px; font-weight: 900; font-size: 1rem; color: var(--text-main);"><i class="fas fa-history" style="color: #6366f1; margin-right: 10px;"></i>Activity Logs</h4>
                <div style="display: grid; gap: 15px;">
                    @forelse($activities as $activity)
                        <div style="display: flex; gap: 12px; margin-bottom: 5px;">
                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #6366f1; margin-top: 6px; flex-shrink: 0;"></div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px;">
                                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 700; line-height: 1.2;">
                                        {{ $activity->module }} 
                                        <span style="font-size: 0.65rem; background: var(--bg-body); padding: 2px 6px; border-radius: 4px; color: var(--secondary); margin-left: 5px; font-weight: 800;">
                                            {{ $activity->action }}
                                        </span>
                                    </div>
                                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 600; text-align: right; white-space: nowrap;">
                                        {{ $activity->created_at->format('M d, h:i A') }}
                                    </div>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--secondary); font-weight: 500; margin-top: 4px; line-height: 1.3;">
                                    {{ $activity->description }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; font-weight: 500;">No system logs generated yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Content -->
        <div style="display: grid; gap: 30px;">
            <div class="card" style="background: var(--bg-card); border-radius: 25px; border: none; box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1); padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h4 style="margin: 0; font-weight: 900; font-size: 1.1rem; color: var(--text-main);"><i class="fas fa-chart-line" style="color: #6366f1; margin-right: 10px;"></i>Login Frequency</h4>
                    <span style="font-size: 0.75rem; color: var(--secondary); font-weight: 600;">Last login: {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                </div>
                <div style="height: 250px;"><canvas id="loginChart"></canvas></div>
            </div>

            <div class="card" style="background: var(--bg-card); border-radius: 25px; border: none; box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1); padding: 30px;">
                <h4 style="margin: 0 0 30px; font-weight: 900; font-size: 1.1rem; color: var(--text-main);"><i class="fas fa-user-gear" style="color: #6366f1; margin-right: 10px;"></i>Account Details</h4>
                <form id="updateAccountForm" onsubmit="submitUpdate(event)" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="file" id="avatarInput" name="avatar" style="display: none;" onchange="previewImage(this)">
                    
                    <div style="display: grid; gap: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">First Name</label>
                                <input type="text" name="first_name" value="{{ $user->first_name }}" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Middle Name</label>
                                <input type="text" name="middle_name" value="{{ $user->middle_name }}" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 80px; gap: 12px;">
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Last Name</label>
                                <input type="text" name="last_name" value="{{ $user->last_name }}" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Suffix</label>
                                <input type="text" name="suffix" value="{{ $user->suffix }}" placeholder="Jr." class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Email Address</label>
                            <input type="email" name="email" value="{{ $user->email }}" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Account Role</label>
                                @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                <select name="role" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                                    <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>System Admin</option>
                                    <option value="coordinator" {{ $user->role == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                    <option value="ojt" {{ $user->role == 'ojt' ? 'selected' : '' }}>OJT Trainee</option>
                                </select>
                                @else
                                <div class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 700; background: var(--bg-body); display: flex; align-items: center; color: var(--secondary); cursor: not-allowed; text-transform: capitalize; font-size: 0.9rem;">
                                    {{ in_array($user->role, ['admin', 'super_admin']) ? 'System Admin' : ($user->role === 'ojt' ? 'OJT Trainee' : $user->role) }}
                                </div>
                                @endif
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Assign To</label>
                                @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                <select name="assign" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;">
                                    <option value="National" {{ $user->assign == 'National' ? 'selected' : '' }}>National</option>
                                    <option value="City" {{ $user->assign == 'City' ? 'selected' : '' }}>City</option>
                                </select>
                                @else
                                <div class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 700; background: var(--bg-body); display: flex; align-items: center; color: var(--secondary); cursor: not-allowed; font-size: 0.9rem;">
                                    {{ $user->assign ?: 'Not Assigned' }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group" style="position: relative;">
                            @php
                                $userAccessList = $user->access ? explode(', ', $user->access) : [];
                            @endphp
                            <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">Positions (Select Multiple)</label>
                            
                            @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                            <div class="custom-dropdown" style="border: 1.5px solid var(--border-color); border-radius: 10px; background: var(--bg-body); cursor: pointer;">
                                <div id="dropdownHeaderShow" class="dropdown-header" onclick="toggleDropdown('permissionsDropdownShow')" style="height: 40px; display: flex; align-items: center; justify-content: space-between; padding: 0 12px; font-weight: 600; color: {{ count($userAccessList) > 0 ? '#6366f1' : 'var(--secondary)' }}; font-size: 0.85rem;">
                                    <span style="{{ count($userAccessList) > 0 ? 'font-weight: 800;' : 'font-weight: 600;' }}">{{ count($userAccessList) > 0 ? count($userAccessList) . ' selected' : 'Select Positions...' }}</span>
                                    <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                                </div>
                                <div class="dropdown-body" id="permissionsDropdownShow" style="display: none; position: absolute; bottom: calc(100% + 5px); left: 0; right: 0; background: var(--bg-card); border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); border: 1.5px solid var(--border-color); z-index: 100;">
                                    <div style="padding: 8px; border-bottom: 1px solid var(--border-color);">
                                        <input type="text" placeholder="Search position..." class="form-control" onkeyup="filterDropdown(this, 'permissionsListShow')" style="border-radius: 6px; height: 32px; border: 1px solid var(--border-color); font-size: 0.8rem; padding-left: 10px; width: 100%; background: var(--bg-body); color: var(--text-main);">
                                    </div>
                                    <div id="permissionsListShow" style="max-height: 180px; overflow-y: auto; padding: 8px;">
                                        @foreach($rolesList as $rListItem)
                                        <label class="dropdown-item-label" style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600; color: var(--text-main); cursor: pointer; padding: 6px 10px; border-radius: 6px; transition: 0.2s;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                                            <input type="checkbox" name="access[]" value="{{ $rListItem }}" style="width: 14px; height: 14px; accent-color: #6366f1;" {{ in_array($rListItem, $userAccessList) ? 'checked' : '' }} onchange="updateDropdownText('permissionsListShow', 'dropdownHeaderShow')">
                                            <span class="item-text">{{ $rListItem }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @else
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; padding: 10px; background: var(--bg-body); border-radius: 10px; border: 1.5px solid var(--border-color);">
                                @forelse($userAccessList as $accessLevel)
                                    <span style="background: rgba(99, 102, 241, 0.1); color: #6366f1; font-weight: 700; font-size: 0.8rem; padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.2);">
                                        {{ trim($accessLevel) }}
                                    </span>
                                @empty
                                    <span style="color: var(--secondary); font-size: 0.8rem; padding: 4px; font-weight: 600;">No positions assigned.</span>
                                @endforelse
                            </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; font-size: 0.7rem; color: var(--secondary); display: block; margin-bottom: 5px; text-transform: uppercase;">New Password</label>
                            <div style="position: relative;">
                                <input type="password" id="updatePasswordInput" name="password" placeholder="Leave blank to keep current" class="form-control" style="border-radius: 10px; height: 40px; border: 1.5px solid var(--border-color); font-weight: 600; background: var(--bg-body); color: var(--text-main); padding-right: 40px; font-size: 0.9rem;">
                                <button type="button" onclick="togglePasswordVisibility('updatePasswordInput', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: none; color: var(--secondary); cursor: pointer; padding:0;">
                                    <i class="fas fa-eye" style="font-size: 0.85rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-secondary" style="border-radius: 10px; font-weight: 800; padding: 10px 25px;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none; border-radius: 10px; font-weight: 800; padding: 10px 35px; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);">
                            Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                if (preview) {
                    preview.src = e.target.result;
                } else {
                    const placeholder = document.getElementById('avatarPlaceholder');
                    const img = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.src = e.target.result;
                    img.style = "width: 100%; height: 100%; border-radius: 30px; object-fit: cover;";
                    placeholder.parentNode.replaceChild(img, placeholder);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function submitUpdate(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const resp = await fetch("{{ route('users.update', $user->id) }}", {
                method: "POST", // Method spoofing will handle the PUT
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Accept": "application/json" },
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                Swal.fire({ title: 'Updated!', text: data.message, icon: 'success' }).then(() => location.reload());
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (e) { Swal.fire('Error!', 'Update system error', 'error'); }
    }

    const ctx = document.getElementById('loginChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($loginData['labels']),
            datasets: [{
                data: @json($loginData['data']),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, 
            scales: { y: { beginAtZero: true, grid: { color: 'var(--border-color)' } }, x: { grid: { display: false } } } }
    });

    // Dropdown Logic
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
        
        if (el.style.display === 'block') {
            setTimeout(() => {
                const searchInput = el.querySelector('input[type="text"]');
                if (searchInput) searchInput.focus();
            }, 100);
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
        const headerContainer = document.querySelector(`#${headerId}`);
        if(checked.length > 0) {
            header.innerText = `${checked.length} selected`;
            headerContainer.style.color = '#6366f1';
            header.style.fontWeight = '800';
        } else {
            header.innerText = 'Select Positions...';
            headerContainer.style.color = '#475569';
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
</script>
@endpush
@endsection
