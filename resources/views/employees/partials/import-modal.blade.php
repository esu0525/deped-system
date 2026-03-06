<div style="padding: 24px;">
    <div style="text-align: center; margin-bottom: 25px;">
        <i class="fas fa-file-excel fa-4x" style="color: #166534; opacity: 0.2; margin-bottom: 15px;"></i>
        <h5 style="font-weight: 800; margin-bottom: 5px;">Import Employee Masterlist</h5>
        <p style="color: var(--secondary); font-size: 0.85rem;">Upload your Excel/CSV file containing employee records.</p>
    </div>

    <form id="importEmployeesForm" action="{{ route('import.employees') }}" method="POST" enctype="multipart/form-data" style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
        @csrf
        <div class="form-group" style="text-align: center; margin-bottom: 0;">
            <label for="importFile" style="display: block; cursor: pointer;">
                <div style="padding: 28px; border: 2px dashed #cbd5e1; border-radius: 12px; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.background='rgba(59, 130, 246, 0.05)'" onmouseout="this.style.borderColor='#cbd5e1';this.style.background='transparent'">
                    <i class="fas fa-cloud-upload-alt fa-2x" style="color: var(--primary); margin-bottom: 10px;"></i>
                    <p style="font-weight: 700; font-size: 0.9rem; margin-bottom: 5px;">Click to select or drag and drop</p>
                    <small style="color: var(--secondary);">Supported formats: .xlsx, .csv (Max 10MB)</small>
                </div>
                <input type="file" id="importFile" name="file" accept=".xlsx, .csv" style="display: none;" onchange="this.form.submit()">
            </label>
        </div>
    </form>

    <div style="margin-top: 25px; padding: 15px; background: rgba(59, 130, 246, 0.05); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.1);">
        <h6 style="font-weight: 700; color: var(--primary); margin-bottom: 8px; font-size: 0.82rem;"><i class="fas fa-info-circle"></i> Mapping Tips:</h6>
        <ul style="padding-left: 20px; margin: 0; font-size: 0.78rem; color: #475569; line-height: 1.6;">
            <li>System will automatically map columns like <span style="font-weight: 700;">Name, Position, School/Department</span>.</li>
            <li>New departments will be created automatically.</li>
            <li>Missing Reference IDs will be auto-generated.</li>
        </ul>
    </div>
</div>
