@extends('layouts.app')

@section('header_title', 'Data Migration & Import')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
        <!-- Import Modules -->
        <div class="card glass">
            <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-file-import text-primary"></i> Import New Data</h4>
            
            <div class="import-block" style="background: #f8fafc; padding: 20px; border-radius: 16px; margin-bottom: 20px;">
                <h5 style="font-weight: 700; margin-bottom: 10px;">Excel Masterlist (Employees)</h5>
                <p style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 15px;">Update or add employee records in bulk. Use the template for correct mapping.</p>
                <form action="{{ route('import.employees') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; gap: 10px;">
                        <input type="file" name="file" class="form-control" style="flex: 1;" required>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>

            <div class="import-block" style="background: #f8fafc; padding: 20px; border-radius: 16px; margin-bottom: 20px;">
                <h5 style="font-weight: 700; margin-bottom: 10px;">Beginning Balances (Leave Cards)</h5>
                <p style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 15px;">Set VL/SL balances for the current or specified year.</p>
                <form action="{{ route('import.leave-cards') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; gap: 10px;">
                        <input type="file" name="file" class="form-control" style="flex: 1;" required>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>

            <div class="import-block" style="background: #f8fafc; padding: 20px; border-radius: 16px;">
                <h5 style="font-weight: 700; margin-bottom: 10px;">Historical Transactions</h5>
                <p style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 15px;">Import past leave ledger entries for auditing and history.</p>
                <form action="{{ route('import.transactions') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; gap: 10px;">
                        <input type="file" name="file" class="form-control" style="flex: 1;" required>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Templates & Logs -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="card glass-dark" style="background: var(--dark);">
                <h5 style="color: white; font-weight: 700; margin-bottom: 20px;"><i class="fas fa-download text-primary"></i> Download Templates</h5>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="{{ route('import.template', 'employees') }}" class="btn btn-outline-primary" style="justify-content: flex-start; background: rgba(255,255,255,0.05);">
                        <i class="fas fa-file-excel"></i> Employee Masterlist Template
                    </a>
                    <a href="{{ route('import.template', 'leave_cards') }}" class="btn btn-outline-primary" style="justify-content: flex-start; background: rgba(255,255,255,0.05);">
                        <i class="fas fa-file-excel"></i> Leave Card Balances Template
                    </a>
                    <a href="{{ route('import.template', 'leave_transactions') }}" class="btn btn-outline-primary" style="justify-content: flex-start; background: rgba(255,255,255,0.05);">
                        <i class="fas fa-file-excel"></i> Ledger Transactions Template
                    </a>
                </div>
            </div>

            <div class="card">
                <h5 style="font-weight: 700; margin-bottom: 20px;">Recent Import Logs</h5>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    @foreach($importLogs as $log)
                    <div style="padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="font-weight: 700; font-size: 0.85rem;">{{ $log->import_type }}</span>
                            <span class="badge" style="background: #f0fdf4; color: #16a34a;">{{ $log->status }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--secondary);">
                            <span>Rows: {{ $log->total_rows }} ({{ $log->success_rows }} success)</span>
                            <span>{{ $log->created_at->format('M d, H:i') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($importLogs->count() == 0)
                    <p style="text-align: center; opacity: 0.5; font-size: 0.8rem;">No imports performed yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
