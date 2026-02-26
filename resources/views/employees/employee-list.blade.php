@extends('layouts.app')

@section('header_title', 'Employee Management')

@section('content')
<div class="animate-fade">
    <div class="card glass animate-fade">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h4 style="font-weight: 700;">Employee Masterlist</h4>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Employee
            </a>
        </div>

        <!-- Filters -->
        <form action="{{ route('employees.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; background: #f8fafc; padding: 20px; border-radius: 16px;">
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" name="search" class="form-control" placeholder="Search name, ID..." value="{{ request('search') }}">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <select name="department" class="form-control">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #eee; color: var(--secondary);">
                        <th style="padding: 15px;">Employee ID</th>
                        <th style="padding: 15px;">Full Name</th>
                        <th style="padding: 15px;">Department</th>
                        <th style="padding: 15px;">Position</th>
                        <th style="padding: 15px;">Status</th>
                        <th style="padding: 15px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if($employees->count() > 0)
                        @foreach($employees as $emp)
                        <tr style="border-bottom: 1px solid #f9f9f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                            <td style="padding: 15px; font-weight: 700; color: var(--primary);">{{ $emp->employee_id }}</td>
                            <td style="padding: 15px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 12px; background: #eee; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                        @if($emp->profile_picture)
                                            <img src="{{ asset('storage/'.$emp->profile_picture) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <i class="fas fa-user-tie" style="color: #cbd5e1;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p style="font-weight: 600; margin: 0;">{{ $emp->full_name }}</p>
                                        <small style="color: var(--secondary);">{{ $emp->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px;">{{ $emp->department?->code ?? 'N/A' }}</td>
                            <td style="padding: 15px;">{{ $emp->position }}</td>
                            <td style="padding: 15px;">
                                <span class="badge" style="background: {{ $emp->status == 'Active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(100, 116, 139, 0.1)' }}; color: {{ $emp->status == 'Active' ? '#059669' : '#475569' }}; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">
                                    {{ $emp->status }}
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-light" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-light" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="{{ route('leave-cards.show', $emp) }}" class="btn btn-sm btn-light" title="Leave Card"><i class="fas fa-address-card"></i></a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: var(--secondary);">
                                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2;"></i>
                                <p>No employees found matching your search.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div style="margin-top: 25px;">
            {{ $employees->links() }}
        </div>
    </div>
</div>
@endsection
