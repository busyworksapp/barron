<?php
/**
 * Barron Production Management System
 * Order Import Wizard
 */

require_once __DIR__ . '/../../includes/auth_check.php';

// Check permissions
if (!checkPermission('planning.create')) {
    header('Location: /pages/dashboard.php?error=access_denied');
    exit;
}

$page_title = 'Import Orders';
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-upload"></i> Import Orders from Excel</h1>
    <div class="header-actions">
        <a href="/pages/planning/orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<!-- Import Wizard -->
<div class="import-wizard">
    <!-- Step Indicator -->
    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Upload File</div>
        </div>
        <div class="wizard-step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Map Columns</div>
        </div>
        <div class="wizard-step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label">Validate</div>
        </div>
        <div class="wizard-step" data-step="4">
            <div class="step-number">4</div>
            <div class="step-label">Import</div>
        </div>
    </div>

    <!-- Step 1: Upload File -->
    <div class="wizard-content" id="step1">
        <div class="card">
            <div class="card-header">
                <h3>Step 1: Upload Excel File</h3>
            </div>
            <div class="card-body">
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drag & Drop your Excel file here</h4>
                    <p>or click to browse</p>
                    <input type="file" id="fileInput" accept=".csv,.xlsx" style="display:none">
                    <button class="btn btn-primary mt-10" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-file-excel"></i> Select File
                    </button>
                </div>
                <div class="file-requirements mt-20">
                    <h5>File Requirements:</h5>
                    <ul>
                        <li>Supported formats: CSV (.csv) or Excel (.xlsx)</li>
                        <li>First row must contain column headers</li>
                        <li>Required columns: Order Number, Customer Name, Due Date</li>
                        <li>Maximum file size: 5MB</li>
                        <li>Maximum rows: 1000</li>
                    </ul>
                </div>
                <div class="sample-download mt-20">
                    <button class="btn btn-outline" onclick="downloadSampleFile()">
                        <i class="fas fa-download"></i> Download Sample Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Map Columns -->
    <div class="wizard-content" id="step2" style="display:none">
        <div class="card">
            <div class="card-header">
                <h3>Step 2: Map Columns</h3>
                <p>Match your Excel columns to the system fields</p>
            </div>
            <div class="card-body">
                <div class="file-info" id="fileInfo"></div>
                <div class="column-mapping" id="columnMapping">
                    <!-- Will be populated dynamically -->
                </div>
                <div class="wizard-actions mt-20">
                    <button class="btn btn-secondary" onclick="goToStep(1)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button class="btn btn-primary" onclick="validateMapping()">
                        Next: Validate <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Validate -->
    <div class="wizard-content" id="step3" style="display:none">
        <div class="card">
            <div class="card-header">
                <h3>Step 3: Validate Data</h3>
                <p>Review data quality before import</p>
            </div>
            <div class="card-body">
                <div id="validationResults">
                    <div class="loading-spinner"></div>
                    Validating data...
                </div>
                <div class="wizard-actions mt-20">
                    <button class="btn btn-secondary" onclick="goToStep(2)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button class="btn btn-primary" id="proceedImportBtn" style="display:none" onclick="proceedToImport()">
                        Next: Import <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Import -->
    <div class="wizard-content" id="step4" style="display:none">
        <div class="card">
            <div class="card-header">
                <h3>Step 4: Import Orders</h3>
            </div>
            <div class="card-body">
                <div id="importProgress">
                    <div class="import-status">
                        <div class="progress-circle">
                            <svg viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="10"></circle>
                                <circle id="progressCircle" cx="50" cy="50" r="45" fill="none" stroke="#3b82f6" stroke-width="10" 
                                        stroke-dasharray="283" stroke-dashoffset="283" transform="rotate(-90 50 50)"></circle>
                            </svg>
                            <div class="progress-text" id="progressText">0%</div>
                        </div>
                        <h4 id="statusMessage">Preparing import...</h4>
                        <p id="statusDetail"></p>
                    </div>
                </div>
                <div id="importResults" style="display:none">
                    <!-- Will be populated after import -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let uploadedFile = null;
let fileHeaders = [];
let columnMapping = {};
let validationResult = null;

document.addEventListener('DOMContentLoaded', function() {
    setupFileUpload();
});

function setupFileUpload() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('drag-over');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        handleFileSelect(file);
    });
    
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        handleFileSelect(file);
    });
}

function handleFileSelect(file) {
    if (!file) return;
    
    // Validate file type
    const validTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!validTypes.includes(file.type) && !file.name.endsWith('.csv')) {
        showAlert('Invalid file type. Please upload a CSV or Excel file.', 'error');
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('File too large. Maximum size is 5MB.', 'error');
        return;
    }
    
    uploadedFile = file;
    
    // Read file to get headers
    readFileHeaders(file);
}

function readFileHeaders(file) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split('\n');
        
        if (lines.length < 2) {
            showAlert('File must contain at least a header row and one data row', 'error');
            return;
        }
        
        // Parse CSV header
        fileHeaders = parseCSVLine(lines[0]);
        
        if (fileHeaders.length === 0) {
            showAlert('Could not parse file headers', 'error');
            return;
        }
        
        // Move to step 2
        showColumnMapping();
        goToStep(2);
    };
    
    reader.readAsText(file);
}

function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;
    
    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        
        if (char === '"') {
            inQuotes = !inQuotes;
        } else if (char === ',' && !inQuotes) {
            result.push(current.trim());
            current = '';
        } else {
            current += char;
        }
    }
    
    result.push(current.trim());
    return result;
}

function showColumnMapping() {
    const container = document.getElementById('columnMapping');
    const fileInfo = document.getElementById('fileInfo');
    
    fileInfo.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-file-excel"></i>
            <strong>File:</strong> ${uploadedFile.name} (${(uploadedFile.size / 1024).toFixed(2)} KB)
        </div>
    `;
    
    const systemFields = [
        { key: 'order_number', label: 'Order Number', required: true },
        { key: 'customer_name', label: 'Customer Name', required: true },
        { key: 'customer_email', label: 'Customer Email', required: false },
        { key: 'customer_phone', label: 'Customer Phone', required: false },
        { key: 'order_date', label: 'Order Date', required: false },
        { key: 'due_date', label: 'Due Date', required: true },
        { key: 'priority', label: 'Priority', required: false },
        { key: 'notes', label: 'Notes', required: false }
    ];
    
    let html = '<div class="mapping-grid">';
    
    systemFields.forEach(field => {
        html += `
            <div class="mapping-row">
                <div class="mapping-field">
                    <label>${field.label} ${field.required ? '<span class="required">*</span>' : ''}</label>
                </div>
                <div class="mapping-arrow">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div class="mapping-select">
                    <select id="map_${field.key}" data-field="${field.key}" onchange="updateMapping()">
                        <option value="">-- Select Column --</option>
                        ${fileHeaders.map((header, index) => 
                            `<option value="${index}">${header}</option>`
                        ).join('')}
                    </select>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Auto-map based on column names
    autoMapColumns(systemFields);
}

function autoMapColumns(fields) {
    fields.forEach(field => {
        const fieldKey = field.key.toLowerCase().replace('_', ' ');
        
        fileHeaders.forEach((header, index) => {
            const headerLower = header.toLowerCase();
            
            if (headerLower.includes(fieldKey) || fieldKey.includes(headerLower)) {
                const select = document.getElementById(`map_${field.key}`);
                if (select) {
                    select.value = index;
                }
            }
        });
    });
    
    updateMapping();
}

function updateMapping() {
    columnMapping = {};
    
    document.querySelectorAll('[id^="map_"]').forEach(select => {
        const field = select.dataset.field;
        const columnIndex = select.value;
        
        if (columnIndex !== '') {
            columnMapping[field] = parseInt(columnIndex);
        }
    });
}

function validateMapping() {
    updateMapping();
    
    // Check required fields
    const required = ['order_number', 'customer_name', 'due_date'];
    const missing = required.filter(field => !columnMapping[field]);
    
    if (missing.length > 0) {
        showAlert(`Please map required fields: ${missing.join(', ')}`, 'error');
        return;
    }
    
    // Proceed to validation
    goToStep(3);
    runValidation();
}

function runValidation() {
    const formData = new FormData();
    formData.append('file', uploadedFile);
    formData.append('column_mapping', JSON.stringify(columnMapping));
    formData.append('action', 'validate');
    
    fetch('/api/planning/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        validationResult = data;
        displayValidationResults(data);
    })
    .catch(error => {
        console.error('Validation error:', error);
        showAlert('Validation failed', 'error');
    });
}

function displayValidationResults(data) {
    const container = document.getElementById('validationResults');
    
    let html = '<div class="validation-summary">';
    
    if (data.success && data.validation.valid) {
        html += `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Validation Passed!</strong>
                <p>${data.validation.total_rows} rows ready to import</p>
            </div>
        `;
        document.getElementById('proceedImportBtn').style.display = 'inline-block';
    } else {
        html += `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Validation Failed</strong>
                <p>${data.validation.message || data.message}</p>
            </div>
        `;
        
        if (data.validation.sample_errors && data.validation.sample_errors.length > 0) {
            html += '<div class="error-list"><h5>Sample Errors:</h5><ul>';
            data.validation.sample_errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul></div>';
        }
    }
    
    html += '</div>';
    container.innerHTML = html;
}

function proceedToImport() {
    goToStep(4);
    startImport();
}

function startImport() {
    const formData = new FormData();
    formData.append('file', uploadedFile);
    formData.append('column_mapping', JSON.stringify(columnMapping));
    formData.append('action', 'import');
    
    updateImportProgress(0, 'Starting import...', '');
    
    fetch('/api/planning/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateImportProgress(100, 'Import Complete!', `Successfully imported ${data.imported_count} orders`);
            displayImportResults(data);
        } else {
            updateImportProgress(0, 'Import Failed', data.message);
            displayImportResults(data);
        }
    })
    .catch(error => {
        console.error('Import error:', error);
        updateImportProgress(0, 'Import Failed', 'An error occurred during import');
    });
}

function updateImportProgress(percent, message, detail) {
    const circle = document.getElementById('progressCircle');
    const text = document.getElementById('progressText');
    const statusMsg = document.getElementById('statusMessage');
    const statusDetail = document.getElementById('statusDetail');
    
    const circumference = 283;
    const offset = circumference - (percent / 100) * circumference;
    
    circle.style.strokeDashoffset = offset;
    text.textContent = percent + '%';
    statusMsg.textContent = message;
    statusDetail.textContent = detail;
}

function displayImportResults(data) {
    setTimeout(() => {
        document.getElementById('importProgress').style.display = 'none';
        
        const resultsContainer = document.getElementById('importResults');
        resultsContainer.style.display = 'block';
        
        let html = '<div class="import-summary">';
        
        if (data.success) {
            html += `
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Import Successful!</h3>
                <div class="summary-stats">
                    <div class="stat">
                        <label>Total Rows</label>
                        <value>${data.total_rows}</value>
                    </div>
                    <div class="stat">
                        <label>Imported</label>
                        <value class="success">${data.imported_count}</value>
                    </div>
                    <div class="stat">
                        <label>Errors</label>
                        <value class="error">${data.errors.length}</value>
                    </div>
                </div>
            `;
            
            if (data.errors.length > 0) {
                html += '<div class="error-details"><h5>Errors:</h5><ul>';
                data.errors.slice(0, 10).forEach(error => {
                    html += `<li>${error}</li>`;
                });
                if (data.errors.length > 10) {
                    html += `<li>... and ${data.errors.length - 10} more errors</li>`;
                }
                html += '</ul></div>';
            }
            
            html += `
                <div class="actions mt-20">
                    <a href="/pages/planning/orders.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View Orders
                    </a>
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <i class="fas fa-upload"></i> Import More
                    </button>
                </div>
            `;
        } else {
            html += `
                <div class="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3>Import Failed</h3>
                <p>${data.message}</p>
                <button class="btn btn-primary mt-20" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            `;
        }
        
        html += '</div>';
        resultsContainer.innerHTML = html;
    }, 1000);
}

function goToStep(step) {
    // Hide all steps
    document.querySelectorAll('.wizard-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Update step indicators
    document.querySelectorAll('.wizard-step').forEach(stepEl => {
        stepEl.classList.remove('active', 'completed');
        const stepNum = parseInt(stepEl.dataset.step);
        if (stepNum < step) {
            stepEl.classList.add('completed');
        } else if (stepNum === step) {
            stepEl.classList.add('active');
        }
    });
    
    // Show current step
    document.getElementById('step' + step).style.display = 'block';
}

function downloadSampleFile() {
    const csv = 'Order Number,Customer Name,Customer Email,Customer Phone,Order Date,Due Date,Priority,Notes\n' +
                'ORD-001,ABC Company,abc@example.com,+1234567890,2024-01-10,2024-01-20,high,Sample order\n' +
                'ORD-002,XYZ Corp,xyz@example.com,+0987654321,2024-01-11,2024-01-25,normal,Another sample';
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'order_import_sample.csv';
    a.click();
}
</script>

<style>
.import-wizard {
    max-width: 900px;
    margin: 0 auto;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    position: relative;
}

.wizard-steps::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 50px;
    right: 50px;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    transition: all 0.3s;
}

.wizard-step.active .step-number {
    background: #3b82f6;
    color: white;
}

.wizard-step.completed .step-number {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 14px;
    color: #666;
    font-weight: 600;
}

.upload-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    padding: 60px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.upload-zone:hover,
.upload-zone.drag-over {
    border-color: #3b82f6;
    background: #eff6ff;
}

.upload-zone i {
    font-size: 64px;
    color: #94a3b8;
    margin-bottom: 20px;
}

.upload-zone h4 {
    margin: 0 0 10px 0;
    color: #334155;
}

.upload-zone p {
    margin: 0;
    color: #64748b;
}

.file-requirements,
.sample-download {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
}

.file-requirements ul {
    margin: 10px 0 0 20px;
    color: #475569;
}

.mapping-grid {
    display: grid;
    gap: 15px;
}

.mapping-row {
    display: grid;
    grid-template-columns: 2fr auto 2fr;
    align-items: center;
    gap: 20px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
}

.mapping-field label {
    font-weight: 600;
    color: #334155;
}

.mapping-arrow {
    color: #94a3b8;
    font-size: 20px;
}

.mapping-select select {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
}

.wizard-actions {
    display: flex;
    justify-content: space-between;
}

.validation-summary,
.import-summary {
    text-align: center;
    padding: 20px;
}

.import-status {
    text-align: center;
    padding: 40px 20px;
}

.progress-circle {
    width: 150px;
    height: 150px;
    margin: 0 auto 30px;
    position: relative;
}

.progress-circle svg {
    transform: rotate(-90deg);
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 32px;
    font-weight: 700;
    color: #3b82f6;
}

.success-icon,
.error-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.success-icon {
    color: #10b981;
}

.error-icon {
    color: #ef4444;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 30px 0;
}

.summary-stats .stat {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
}

.summary-stats label {
    display: block;
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
}

.summary-stats value {
    display: block;
    font-size: 32px;
    font-weight: 700;
    color: #334155;
}

.summary-stats value.success {
    color: #10b981;
}

.summary-stats value.error {
    color: #ef4444;
}

.error-list,
.error-details {
    text-align: left;
    margin-top: 20px;
    padding: 20px;
    background: #fef2f2;
    border-radius: 8px;
    border-left: 4px solid #ef4444;
}

.error-list ul,
.error-details ul {
    margin: 10px 0 0 20px;
    color: #991b1b;
}

@media (max-width: 768px) {
    .wizard-steps {
        flex-wrap: wrap;
    }
    
    .mapping-row {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .mapping-arrow {
        transform: rotate(90deg);
    }
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
