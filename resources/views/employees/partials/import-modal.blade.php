<div class="modal-body animate-fade">
    <div style="text-align: center; margin-bottom: 25px;">
        <i class="fas fa-file-excel fa-4x" style="color: #166534; opacity: 0.2; margin-bottom: 15px;"></i>
        <h5 style="font-weight: 800; margin-bottom: 5px;">Import Employee Masterlist</h5>
        <p style="color: var(--secondary); font-size: 0.85rem;">Upload your Excel/CSV file containing employee records.</p>
    </div>

    <form id="importEmployeesForm" action="{{ route('import.employees') }}" method="POST" enctype="multipart/form-data" style="background: #f8fafc; padding: 25px; border-radius: 16px; border: 2px dashed #e2e8f0;">
        @csrf
        <div class="form-group" style="text-align: center; margin-bottom: 0;">
            <label for="importFile" style="display: block; cursor: pointer;">
                <div style="padding: 20px;">
                    <i class="fas fa-cloud-upload-alt fa-2x" style="color: var(--primary); margin-bottom: 10px;"></i>
                    <p style="font-weight: 700; font-size: 0.9rem; margin-bottom: 5px;">Click to select or drag and drop</p>
                    <small style="color: var(--secondary);">Supported formats: .xlsx, .csv (Max 10MB)</small>
                </div>
            </label>
            <input type="file" id="importFile" name="file" class="form-control" accept=".xlsx, .csv" required style="display: none;" onchange="updateFileLabel(this)">
            <div id="fileSelectedName" style="margin-top: 10px; font-weight: 700; color: var(--success); display: none;"></div>
        </div>

        <div style="margin-top: 25px;">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 0.9rem;">
                <i class="fas fa-check-circle"></i> Start Importing Now
            </button>
        </div>
    </form>

    <div style="margin-top: 20px; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px;">
        <div style="display: flex; gap: 12px; align-items: flex-start;">
            <i class="fas fa-info-circle" style="color: #d97706; margin-top: 2px;"></i>
            <div>
                <p style="font-weight: 700; font-size: 0.8rem; color: #92400e; margin-bottom: 4px;">Don't have the template yet?</p>
                <a href="{{ route('import.template', 'employees') }}" style="color: var(--primary); font-size: 0.75rem; font-weight: 700; text-decoration: underline;">
                    <i class="fas fa-download"></i> Download Employee Template here
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function updateFileLabel(input) {
        const fileName = input.files[0] ? input.files[0].name : '';
        const display = document.getElementById('fileSelectedName');
        if (fileName) {
            display.innerText = 'Selected: ' + fileName;
            display.style.display = 'block';
        } else {
            display.style.display = 'none';
        }
    }
</script>
