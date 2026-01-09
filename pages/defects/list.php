<?php
/**
 * Barron Production Management System
 * Defects List Page
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

// Check permission
if (!checkPermission('defects.view')) {
    header('Location: /pages/dashboard.php');
    exit;
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">📋 Defect Tracking</h2>
            <p class="text-muted mb-0">Monitor and manage production defects</p>
        </div>
        <?php if (checkPermission('defects.create')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportDefectModal">
            <i class="bi bi-plus-circle"></i> Report Defect
        </button>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statsCards">
        <!-- Stats will be loaded via AJAX -->
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Defect number, product...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Severity</label>
                    <select class="form-select" id="severityFilter">
                        <option value="">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" id="dateToFilter">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-secondary w-100" onclick="clearFilters()">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Defects Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Defect #</th>
                            <th>Date</th>
                            <th>Job/Order</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Replacement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="defectsTableBody">
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Defects pagination" id="paginationContainer">
                <!-- Pagination will be loaded via AJAX -->
            </nav>
        </div>
    </div>
</div>

<!-- Report Defect Modal -->
<div class="modal fade" id="reportDefectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Defect</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reportDefectForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Job Number *</label>
                            <input type="text" class="form-control" name="job_number" required 
                                   placeholder="Search job number..." id="jobNumberInput">
                            <input type="hidden" name="job_id" id="jobIdInput">
                            <div id="jobSearchResults" class="list-group mt-1"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity Affected *</label>
                            <input type="number" class="form-control" name="quantity" required min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Severity *</label>
                            <select class="form-select" name="severity" required>
                                <option value="">Select severity...</option>
                                <option value="critical">🔴 Critical - Production stopped</option>
                                <option value="high">🟠 High - Significant impact</option>
                                <option value="medium">🟡 Medium - Moderate impact</option>
                                <option value="low">🟢 Low - Minor impact</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Replacement Required</label>
                            <select class="form-select" name="requires_replacement">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Defect Description *</label>
                            <textarea class="form-control" name="description" rows="3" required 
                                      placeholder="Describe the defect in detail..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Root Cause</label>
                            <textarea class="form-control" name="root_cause" rows="2" 
                                      placeholder="If known, describe the root cause..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Corrective Action</label>
                            <textarea class="form-control" name="corrective_action" rows="2" 
                                      placeholder="Actions taken or planned..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Report Defect</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Defect Modal -->
<div class="modal fade" id="viewDefectModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Defect Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="defectDetailsContent">
                <!-- Details will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {};

// Load statistics
function loadStatistics() {
    fetch('/api/defects/defects.php?action=statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.statistics;
                document.getElementById('statsCards').innerHTML = `
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.total_defects}</h3>
                                <p class="mb-0">Total Defects</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.open_defects + stats.in_progress_defects}</h3>
                                <p class="mb-0">Active Defects</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.critical_defects}</h3>
                                <p class="mb-0">Critical Defects</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.replacement_tickets}</h3>
                                <p class="mb-0">Replacement Tickets</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
}

// Load defects
function loadDefects(page = 1) {
    currentPage = page;
    
    const params = new URLSearchParams({
        action: 'list',
        page: page,
        ...currentFilters
    });
    
    fetch(`/api/defects/defects.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDefects(data.defects);
                renderPagination(data.pagination);
            }
        });
}

// Render defects table
function renderDefects(defects) {
    const tbody = document.getElementById('defectsTableBody');
    
    if (defects.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No defects found</td></tr>';
        return;
    }
    
    tbody.innerHTML = defects.map(defect => `
        <tr>
            <td><strong>${defect.defect_number}</strong></td>
            <td>${new Date(defect.created_at).toLocaleDateString()}</td>
            <td>
                <div>${defect.job_number}</div>
                <small class="text-muted">${defect.order_number}</small>
            </td>
            <td>${defect.product_name || defect.product_code}</td>
            <td>${defect.quantity}</td>
            <td>${getSeverityBadge(defect.severity)}</td>
            <td>${getStatusBadge(defect.status)}</td>
            <td>${defect.requires_replacement ? '<span class="badge bg-warning">Yes</span>' : '-'}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="viewDefect(${defect.id})">
                    <i class="bi bi-eye"></i>
                </button>
                ${defect.status !== 'closed' && <?= checkPermission('defects.edit') ? 'true' : 'false' ?> ? `
                    <button class="btn btn-sm btn-primary" onclick="updateStatus(${defect.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

// Get severity badge
function getSeverityBadge(severity) {
    const badges = {
        'critical': '<span class="badge bg-danger">🔴 Critical</span>',
        'high': '<span class="badge bg-warning">🟠 High</span>',
        'medium': '<span class="badge bg-info">🟡 Medium</span>',
        'low': '<span class="badge bg-success">🟢 Low</span>'
    };
    return badges[severity] || severity;
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'open': '<span class="badge bg-danger">Open</span>',
        'in_progress': '<span class="badge bg-warning">In Progress</span>',
        'resolved': '<span class="badge bg-success">Resolved</span>',
        'closed': '<span class="badge bg-secondary">Closed</span>'
    };
    return badges[status] || status;
}

// Render pagination
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<ul class="pagination">';
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadDefects(${i}); return false;">${i}</a>
        </li>`;
    }
    
    html += '</ul>';
    container.innerHTML = html;
}

// View defect details
function viewDefect(id) {
    fetch(`/api/defects/defects.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const d = data.defect;
                document.getElementById('defectDetailsContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr><th>Defect Number:</th><td>${d.defect_number}</td></tr>
                                <tr><th>Job:</th><td>${d.job_number}</td></tr>
                                <tr><th>Order:</th><td>${d.order_number}</td></tr>
                                <tr><th>Product:</th><td>${d.product_name}</td></tr>
                                <tr><th>Quantity:</th><td>${d.quantity}</td></tr>
                                <tr><th>Severity:</th><td>${getSeverityBadge(d.severity)}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr><th>Status:</th><td>${getStatusBadge(d.status)}</td></tr>
                                <tr><th>Reported By:</th><td>${d.reporter_name}</td></tr>
                                <tr><th>Reported On:</th><td>${new Date(d.created_at).toLocaleString()}</td></tr>
                                <tr><th>Department:</th><td>${d.department_name}</td></tr>
                                <tr><th>Replacement:</th><td>${d.requires_replacement ? 'Yes' : 'No'}</td></tr>
                                ${d.resolved_at ? `<tr><th>Resolved On:</th><td>${new Date(d.resolved_at).toLocaleString()}</td></tr>` : ''}
                            </table>
                        </div>
                        <div class="col-12">
                            <h6>Description:</h6>
                            <p>${d.description}</p>
                            ${d.root_cause ? `<h6>Root Cause:</h6><p>${d.root_cause}</p>` : ''}
                            ${d.corrective_action ? `<h6>Corrective Action:</h6><p>${d.corrective_action}</p>` : ''}
                        </div>
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('viewDefectModal')).show();
            }
        });
}

// Report defect form submission
document.getElementById('reportDefectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    fetch('/api/defects/defects.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Defect reported successfully');
            bootstrap.Modal.getInstance(document.getElementById('reportDefectModal')).hide();
            this.reset();
            loadDefects();
            loadStatistics();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Job number search
let jobSearchTimeout;
document.getElementById('jobNumberInput').addEventListener('input', function() {
    clearTimeout(jobSearchTimeout);
    const query = this.value;
    
    if (query.length < 2) {
        document.getElementById('jobSearchResults').innerHTML = '';
        return;
    }
    
    jobSearchTimeout = setTimeout(() => {
        fetch(`/api/planning/jobs.php?search=${query}&limit=5`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.jobs.length > 0) {
                    document.getElementById('jobSearchResults').innerHTML = data.jobs.map(job => `
                        <button type="button" class="list-group-item list-group-item-action" 
                                onclick="selectJob(${job.id}, '${job.job_number}')">
                            ${job.job_number} - ${job.product_name}
                        </button>
                    `).join('');
                }
            });
    }, 300);
});

function selectJob(id, number) {
    document.getElementById('jobIdInput').value = id;
    document.getElementById('jobNumberInput').value = number;
    document.getElementById('jobSearchResults').innerHTML = '';
}

// Filter handlers
['searchInput', 'statusFilter', 'severityFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        currentFilters[id.replace('Filter', '').replace('Input', '')] = this.value;
        loadDefects(1);
    });
});

function clearFilters() {
    ['searchInput', 'statusFilter', 'severityFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
        document.getElementById(id).value = '';
    });
    currentFilters = {};
    loadDefects(1);
}

// Initial load
loadStatistics();
loadDefects();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
