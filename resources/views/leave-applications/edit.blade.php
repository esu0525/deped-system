@extends('layouts.app')

@section('header_title', 'Edit Leave Application')

@section('content')
<div class="animate-fade">
    <div class="card glass">
        <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-edit text-primary"></i> Edit Application #{{ $leaveApplication->application_no }}</h4>

        <form action="{{ route('leave-applications.update', $leaveApplication) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Type of Leave</label>
                <select name="leave_type_id" class="form-control" required>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ $leaveApplication->leave_type_id == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $leaveApplication->date_from->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $leaveApplication->date_to->format('Y-m-d')) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="4">{{ old('reason', $leaveApplication->reason) }}</textarea>
            </div>




            <div style="display: flex; gap: 15px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Application</button>
                <a href="{{ route('leave-applications.show', $leaveApplication) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
