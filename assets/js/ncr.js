// NCR Management
let currentEditId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryStats();
    loadNCRs();
    loadDepartments();
    loadEmployees();
    generateNCRNumber();
    
    // Set default date to today
    document.getElementById('date_raised').valueAsDate = new Date();
    
    // Filter event listeners
    document.getElementById('searchInput').addEventListener('input', debounce(loadNCRs, 300));
    document.getElementById('statusFilter').addEventListener('change', loadNCRs);
    document.getElementById('typeFilter').addEventListener('change', loadNCRs);
    document.getElementById('departmentFilter').addEventListener('change', loadNCRs);
    document.getElementById('dateFromFilter').addEventListener('change', loadNCRs);
    
    // Form submit
    document.getElementById('ncrForm').addEventListener('submit', saveNCR);
});

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Generate NCR number
function generateNCRNumber() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    document.getElementById('ncr_number').value = `NCR${year}${month}${random}`;
}

// Load summary statistics
async function loadSummaryStats() {
    try {
        const response = await fetch('../../api/sop/ncr/stats.php');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('openNCRCount').textContent = result.data.open_count;
            document.getElementById('closedNCRCount').textContent = result.data.closed_count;
            document.getElementById('thisMonthCount').textContent = result.data.this_month_count;
            document.getElementById('overdueCount').textContent = result.data.overdue_count;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load NCRs
async function loadNCRs() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    const department = document.getElementById('departmentFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    
    const params = new URLSearchParams({
        search: search,
        status: status,
        ncr_type: type,
        department_id: department,
        date_from: dateFrom
    });
    
    try {
        const response = await fetch(`../../api/sop/ncr/list.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            displayNCRs(result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading NCRs:', error);
        showError('Error loading NCRs');
    }
}

// Display NCRs
function displayNCRs(ncrs) {
    const tbody = document.getElementById('ncrTableBody');
    
    if (ncrs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No NCRs found</td></tr>';
        return;
    }
    
    tbody.innerHTML = ncrs.map(ncr => {
        const isOverdue = ncr.target_closure_date && 
                         new Date(ncr.target_closure_date) < new Date() && 
                         ncr.status !== 'closed';
        
        return `
            <tr ${isOverdue ? 'style="background-color: #fff5f5;"' : ''}>
                <td><strong>${escapeHtml(ncr.ncr_number)}</strong></td>
                <td>${getTypeBadge(ncr.ncr_type)}</td>
                <td>
                    <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(ncr.description)}
                    </div>
                </td>
                <td>${escapeHtml(ncr.department_name)}</td>
                <td>${getStatusBadge(ncr.status)}</td>
                <td>${formatDate(ncr.date_raised)}</td>
                <td>
                    ${ncr.target_closure_date ? formatDate(ncr.target_closure_date) : 'Not set'}
                    ${isOverdue ? '<span style="color: red; font-weight: bold;"> ⚠️ OVERDUE</span>' : ''}
                </td>
                <td>${escapeHtml(ncr.raised_by_name)}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn-icon" onclick="viewNCR(${ncr.id})" title="View Details">👁️</button>
                        ${ncr.status !== 'closed' ? `
                            <button class="btn-icon" onclick="editNCR(${ncr.id})" title="Edit">✏️</button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Get type badge
function getTypeBadge(type) {
    const badges = {
        'internal': '<span class="badge badge-info">Internal</span>',
        'supplier': '<span class="badge badge-warning">Supplier</span>',
        'customer': '<span class="badge badge-danger">Customer</span>'
    };
    return badges[type] || '<span class="badge">Unknown</span>';
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'open': '<span class="badge badge-info">Open</span>',
        'investigation': '<span class="badge badge-warning">Investigation</span>',
        'capa_pending': '<span class="badge badge-danger">CAPA Pending</span>',
        'capa_in_progress': '<span class="badge badge-primary">CAPA In Progress</span>',
        'verification': '<span class="badge badge-warning">Verification</span>',
        'closed': '<span class="badge badge-success">Closed</span>'
    };
    return badges[status] || '<span class="badge">Unknown</span>';
}

// Load departments
async function loadDepartments() {
    try {
        const response = await fetch('../../api/master/departments/list.php');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('department_id');
            const filterSelect = document.getElementById('departmentFilter');
            
            result.data.forEach(dept => {
                select.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.name)}</option>`;
                filterSelect.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading departments:', error);
    }
}

// Load employees
async function loadEmployees() {
    try {
        const response = await fetch('../../api/master/employees/list.php');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('assigned_to');
            result.data.forEach(emp => {
                select.innerHTML += `<option value="${emp.id}">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading employees:', error);
    }
}

// Show NCR modal
function showNCRModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Create NCR';
    document.getElementById('ncrForm').reset();
    generateNCRNumber();
    document.getElementById('date_raised').valueAsDate = new Date();
    document.getElementById('ncrModal').classList.add('active');
}

// Close NCR modal
function closeNCRModal() {
    document.getElementById('ncrModal').classList.remove('active');
    currentEditId = null;
}

// Edit NCR
async function editNCR(id) {
    try {
        const response = await fetch(`../../api/sop/ncr/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            currentEditId = id;
            const ncr = result.data;
            
            document.getElementById('modalTitle').textContent = 'Edit NCR';
            document.getElementById('ncr_id').value = ncr.id;
            document.getElementById('ncr_number').value = ncr.ncr_number;
            document.getElementById('ncr_type').value = ncr.ncr_type;
            document.getElementById('department_id').value = ncr.department_id;
            document.getElementById('date_raised').value = ncr.date_raised;
            document.getElementById('description').value = ncr.description;
            document.getElementById('immediate_action').value = ncr.immediate_action || '';
            document.getElementById('root_cause').value = ncr.root_cause || '';
            document.getElementById('corrective_action').value = ncr.corrective_action || '';
            document.getElementById('preventive_action').value = ncr.preventive_action || '';
            document.getElementById('assigned_to').value = ncr.assigned_to || '';
            document.getElementById('target_closure_date').value = ncr.target_closure_date || '';
            document.getElementById('status').value = ncr.status;
            document.getElementById('verification_notes').value = ncr.verification_notes || '';
            
            document.getElementById('ncrModal').classList.add('active');
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading NCR:', error);
        showError('Error loading NCR details');
    }
}

// Save NCR
async function saveNCR(e) {
    e.preventDefault();
    
    const formData = {
        ncr_id: document.getElementById('ncr_id').value,
        ncr_number: document.getElementById('ncr_number').value,
        ncr_type: document.getElementById('ncr_type').value,
        department_id: document.getElementById('department_id').value,
        date_raised: document.getElementById('date_raised').value,
        description: document.getElementById('description').value,
        immediate_action: document.getElementById('immediate_action').value,
        root_cause: document.getElementById('root_cause').value,
        corrective_action: document.getElementById('corrective_action').value,
        preventive_action: document.getElementById('preventive_action').value,
        assigned_to: document.getElementById('assigned_to').value || null,
        target_closure_date: document.getElementById('target_closure_date').value || null,
        status: document.getElementById('status').value,
        verification_notes: document.getElementById('verification_notes').value
    };
    
    const url = formData.ncr_id 
        ? '../../api/sop/ncr/update.php'
        : '../../api/sop/ncr/create.php';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeNCRModal();
            loadNCRs();
            loadSummaryStats();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error saving NCR:', error);
        showError('Error saving NCR');
    }
}

// View NCR
async function viewNCR(id) {
    try {
        const response = await fetch(`../../api/sop/ncr/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            displayNCRDetails(result.data);
            document.getElementById('viewNCRModal').classList.add('active');
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading NCR:', error);
        showError('Error loading NCR details');
    }
}

// Display NCR details
function displayNCRDetails(ncr) {
    const content = document.getElementById('ncrDetailsContent');
    const isOverdue = ncr.target_closure_date && 
                     new Date(ncr.target_closure_date) < new Date() && 
                     ncr.status !== 'closed';
    
    content.innerHTML = `
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-label">NCR Number:</div>
                <div class="detail-value"><strong>${escapeHtml(ncr.ncr_number)}</strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Type:</div>
                <div class="detail-value">${getTypeBadge(ncr.ncr_type)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value">${escapeHtml(ncr.department_name)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">${getStatusBadge(ncr.status)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date Raised:</div>
                <div class="detail-value">${formatDate(ncr.date_raised)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Target Closure:</div>
                <div class="detail-value">
                    ${ncr.target_closure_date ? formatDate(ncr.target_closure_date) : 'Not set'}
                    ${isOverdue ? '<span style="color: red; font-weight: bold;"> ⚠️ OVERDUE</span>' : ''}
                </div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Description:</div>
                <div class="detail-value">${escapeHtml(ncr.description)}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Immediate Containment Action:</div>
                <div class="detail-value">${escapeHtml(ncr.immediate_action || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Root Cause Analysis:</div>
                <div class="detail-value">${escapeHtml(ncr.root_cause || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Corrective Action (CA):</div>
                <div class="detail-value">${escapeHtml(ncr.corrective_action || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Preventive Action (PA):</div>
                <div class="detail-value">${escapeHtml(ncr.preventive_action || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Verification Notes:</div>
                <div class="detail-value">${escapeHtml(ncr.verification_notes || 'N/A')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Assigned To:</div>
                <div class="detail-value">${escapeHtml(ncr.assigned_to_name || 'Unassigned')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Raised By:</div>
                <div class="detail-value">${escapeHtml(ncr.raised_by_name)}</div>
            </div>
            ${ncr.closed_by_name ? `
                <div class="detail-row">
                    <div class="detail-label">Closed By:</div>
                    <div class="detail-value">${escapeHtml(ncr.closed_by_name)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Closed Date:</div>
                    <div class="detail-value">${formatDateTime(ncr.closed_date)}</div>
                </div>
            ` : ''}
        </div>
    `;
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewNCRModal').classList.remove('active');
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-GB');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert('Error: ' + message);
}
