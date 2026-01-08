// Internal Rejects JavaScript
let departments = [];
let activeJobs = [];
let currentRejectId = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDepartments();
    loadActiveJobs();
    loadRejects();
    loadSummaryStats();
    
    // Setup form submissions
    document.getElementById('rejectForm').addEventListener('submit', saveReject);
    document.getElementById('approvalForm').addEventListener('submit', submitApproval);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadRejects();
        }
    });
    
    // Set default reject date to today
    const today = new Date();
    const dateStr = today.toISOString().split('T')[0];
    document.getElementById('reject_date').value = dateStr;
});

// Load departments for filter
function loadDepartments() {
    fetch('../../api/master/departments/list.php?is_active=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                departments = data.data;
                const filter = document.getElementById('departmentFilter');
                filter.innerHTML = '<option value="">All Departments</option>';
                departments.forEach(dept => {
                    filter.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading departments:', error));
}

// Load active jobs for reject logging
function loadActiveJobs() {
    fetch('../../api/planning/jobs/list.php?status=in_progress,completed')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                activeJobs = data.data;
                const select = document.getElementById('job_id');
                select.innerHTML = '<option value="">Select Job</option>';
                
                activeJobs.forEach(job => {
                    select.innerHTML += `<option value="${job.id}">${escapeHtml(job.job_number)} - ${escapeHtml(job.product_code)} (${escapeHtml(job.department_name)})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading jobs:', error));
}

// Load job info when selected
function loadJobInfo() {
    const jobId = document.getElementById('job_id').value;
    
    if (!jobId) {
        document.getElementById('jobInfoSection').style.display = 'none';
        return;
    }
    
    fetch(`../../api/planning/production/job-progress.php?job_id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const job = data.data;
                document.getElementById('jobProduct').textContent = `${job.product_code} - ${job.product_name}`;
                document.getElementById('jobOrder').textContent = job.order_number || '-';
                document.getElementById('jobDepartment').textContent = job.department_name;
                document.getElementById('jobProduced').textContent = job.produced_quantity || 0;
                
                // Set max for quantity rejected
                document.getElementById('quantity_rejected').max = job.produced_quantity || 0;
                
                document.getElementById('jobInfoSection').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading job details', 'error');
        });
}

// Load summary statistics
function loadSummaryStats() {
    fetch('../../api/defects/rejects/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('pendingRejectsCount').textContent = stats.pending_count || 0;
                document.getElementById('approvedRejectsCount').textContent = stats.approved_count || 0;
                document.getElementById('thisMonthRejectsCount').textContent = stats.this_month_count || 0;
                document.getElementById('rejectRatePercent').textContent = stats.reject_rate + '%';
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Load rejects with filters
function loadRejects() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const department = document.getElementById('departmentFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    
    let url = '../../api/defects/rejects/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (status) url += `status=${status}&`;
    if (department) url += `department_id=${department}&`;
    if (dateFrom) url += `date_from=${dateFrom}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRejects(data.data);
            } else {
                showAlert('Error loading rejects: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading rejects', 'error');
        });
}

// Display rejects in table
function displayRejects(rejects) {
    const tbody = document.getElementById('rejectsTableBody');
    
    if (rejects.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No rejects found</td></tr>';
        return;
    }
    
    let html = '';
    rejects.forEach(reject => {
        const statusBadge = getStatusBadge(reject.status);
        const defectTypeBadge = getDefectTypeBadge(reject.defect_type);
        
        html += `
            <tr>
                <td>${escapeHtml(reject.reject_number)}</td>
                <td>${escapeHtml(reject.job_number)}</td>
                <td>${escapeHtml(reject.product_code)}<br><small>${escapeHtml(reject.product_name)}</small></td>
                <td>${escapeHtml(reject.department_name)}</td>
                <td><span class="badge badge-danger">${reject.quantity_rejected}</span></td>
                <td>${defectTypeBadge}</td>
                <td>${escapeHtml(reject.reported_by_name)}</td>
                <td>${formatDate(reject.reject_date)}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-secondary" onclick="viewReject(${reject.id})" title="View Details">
                        <span class="icon">👁</span>
                    </button>
                    ${reject.status === 'pending' ? `
                        <button class="btn-action btn-success" onclick="openApprovalModal(${reject.id})" title="Approve/Reject">
                            <span class="icon">✓</span>
                        </button>
                    ` : ''}
                    ${reject.status === 'pending' ? `
                        <button class="btn-action btn-edit" onclick="editReject(${reject.id})" title="Edit">
                            <span class="icon">✎</span>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-warning">Pending</span>',
        'approved': '<span class="badge badge-success">Approved</span>',
        'rejected': '<span class="badge badge-danger">Rejected</span>'
    };
    return badges[status] || status;
}

// Get defect type badge
function getDefectTypeBadge(type) {
    const labels = {
        'material_defect': 'Material Defect',
        'workmanship': 'Workmanship',
        'machine_error': 'Machine Error',
        'measurement_error': 'Measurement',
        'color_mismatch': 'Color Mismatch',
        'contamination': 'Contamination',
        'incomplete': 'Incomplete',
        'damaged': 'Damaged',
        'other': 'Other'
    };
    return `<span class="badge badge-info">${labels[type] || type}</span>`;
}

// Open reject modal
function openRejectModal() {
    currentRejectId = null;
    document.getElementById('modalTitle').textContent = 'Log Internal Reject';
    document.getElementById('rejectForm').reset();
    document.getElementById('reject_id').value = '';
    document.getElementById('jobInfoSection').style.display = 'none';
    
    // Generate reject number
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    document.getElementById('reject_number').value = `REJ${year}${month}${random}`;
    
    // Set default date
    document.getElementById('reject_date').value = today.toISOString().split('T')[0];
    
    loadActiveJobs();
    document.getElementById('rejectModal').classList.add('active');
}

// Close reject modal
function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
}

// Save reject
function saveReject(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const rejectId = document.getElementById('reject_id').value;
    
    const data = {
        reject_number: formData.get('reject_number'),
        job_id: formData.get('job_id'),
        quantity_rejected: formData.get('quantity_rejected'),
        defect_type: formData.get('defect_type'),
        defect_description: formData.get('defect_description'),
        root_cause: formData.get('root_cause'),
        reject_date: formData.get('reject_date'),
        disposition: formData.get('disposition')
    };
    
    if (rejectId) {
        data.reject_id = rejectId;
    }
    
    const url = rejectId 
        ? '../../api/defects/rejects/update.php'
        : '../../api/defects/rejects/create.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(rejectId ? 'Reject updated successfully' : 'Reject logged successfully', 'success');
            closeRejectModal();
            loadRejects();
            loadSummaryStats();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving reject', 'error');
    });
}

// Edit reject
function editReject(id) {
    fetch(`../../api/defects/rejects/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const reject = data.data;
                currentRejectId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Internal Reject';
                document.getElementById('reject_id').value = reject.id;
                document.getElementById('reject_number').value = reject.reject_number;
                document.getElementById('job_id').value = reject.job_id;
                document.getElementById('quantity_rejected').value = reject.quantity_rejected;
                document.getElementById('defect_type').value = reject.defect_type;
                document.getElementById('defect_description').value = reject.defect_description;
                document.getElementById('root_cause').value = reject.root_cause || '';
                document.getElementById('reject_date').value = reject.reject_date;
                document.getElementById('disposition').value = reject.disposition;
                
                loadJobInfo();
                document.getElementById('rejectModal').classList.add('active');
            } else {
                showAlert('Error loading reject: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading reject', 'error');
        });
}

// Open approval modal
function openApprovalModal(id) {
    fetch(`../../api/defects/rejects/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const reject = data.data;
                
                document.getElementById('approval_reject_id').value = reject.id;
                document.getElementById('approvalRejectInfo').innerHTML = `
                    <strong>${escapeHtml(reject.reject_number)}</strong><br>
                    Job: ${escapeHtml(reject.job_number)}<br>
                    Product: ${escapeHtml(reject.product_code)} - ${escapeHtml(reject.product_name)}<br>
                    Quantity: ${reject.quantity_rejected}<br>
                    Defect: ${escapeHtml(reject.defect_type)}<br>
                    Description: ${escapeHtml(reject.defect_description)}
                `;
                
                document.getElementById('approvalForm').reset();
                document.getElementById('approvalModal').classList.add('active');
            } else {
                showAlert('Error loading reject: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading reject', 'error');
        });
}

// Close approval modal
function closeApprovalModal() {
    document.getElementById('approvalModal').classList.remove('active');
}

// Submit approval decision
function submitApproval(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const data = {
        reject_id: document.getElementById('approval_reject_id').value,
        decision: formData.get('decision'),
        notes: formData.get('notes')
    };
    
    fetch('../../api/defects/rejects/approve.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Decision submitted successfully', 'success');
            closeApprovalModal();
            loadRejects();
            loadSummaryStats();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error submitting decision', 'error');
    });
}

// View reject details
function viewReject(id) {
    document.getElementById('viewRejectNumber').textContent = '';
    document.getElementById('rejectDetailsContent').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../../api/defects/rejects/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRejectDetails(data.data);
                document.getElementById('viewModal').classList.add('active');
            } else {
                showAlert('Error loading reject: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading reject', 'error');
        });
}

// Display reject details
function displayRejectDetails(reject) {
    document.getElementById('viewRejectNumber').textContent = reject.reject_number;
    
    let html = `
        <div class="form-row">
            <div class="form-group">
                <strong>Status:</strong> ${getStatusBadge(reject.status)}
            </div>
            <div class="form-group">
                <strong>Reject Date:</strong> ${formatDate(reject.reject_date)}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Job:</strong> ${escapeHtml(reject.job_number)}
            </div>
            <div class="form-group">
                <strong>Product:</strong> ${escapeHtml(reject.product_code)} - ${escapeHtml(reject.product_name)}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Department:</strong> ${escapeHtml(reject.department_name)}
            </div>
            <div class="form-group">
                <strong>Quantity Rejected:</strong> <span class="badge badge-danger">${reject.quantity_rejected}</span>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Defect Type:</strong> ${getDefectTypeBadge(reject.defect_type)}
            </div>
            <div class="form-group">
                <strong>Disposition:</strong> ${escapeHtml(reject.disposition)}
            </div>
        </div>
        <div class="form-group">
            <strong>Defect Description:</strong><br>
            ${escapeHtml(reject.defect_description)}
        </div>
    `;
    
    if (reject.root_cause) {
        html += `
            <div class="form-group">
                <strong>Root Cause:</strong><br>
                ${escapeHtml(reject.root_cause)}
            </div>
        `;
    }
    
    html += `
        <div class="form-group">
            <strong>Reported By:</strong> ${escapeHtml(reject.reported_by_name)} on ${formatDate(reject.created_at)}
        </div>
    `;
    
    if (reject.status !== 'pending') {
        html += `
            <div class="form-group" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                <strong>Approval Decision:</strong> ${getStatusBadge(reject.status)}<br>
                <strong>Approved/Rejected By:</strong> ${escapeHtml(reject.approved_by_name)}<br>
                <strong>Date:</strong> ${formatDate(reject.approval_date)}<br>
                ${reject.approval_notes ? `<strong>Notes:</strong> ${escapeHtml(reject.approval_notes)}` : ''}
            </div>
        `;
    }
    
    document.getElementById('rejectDetailsContent').innerHTML = html;
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '10000';
    alertDiv.style.minWidth = '300px';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
