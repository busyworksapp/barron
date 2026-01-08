// Production Tracking JavaScript
let departments = [];
let activeJobs = [];
let currentJobId = null;
let currentUser = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCurrentUser();
    loadDepartments();
    loadJobs();
    loadActiveJobsForProgress();
    loadSummaryStats();
    
    // Setup form submissions
    document.getElementById('progressForm').addEventListener('submit', saveProgress);
    document.getElementById('statusForm').addEventListener('submit', updateJobStatus);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadJobs();
        }
    });
    
    // Set default log time to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('log_time').value = now.toISOString().slice(0, 16);
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
        loadJobs();
        loadSummaryStats();
    }, 30000);
});

// Load current user info
function loadCurrentUser() {
    fetch('../../api/auth/me.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.data;
            }
        })
        .catch(error => console.error('Error loading user:', error));
}

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

// Load active jobs for progress logging
function loadActiveJobsForProgress() {
    fetch('../../api/planning/jobs/list.php?status=scheduled,in_progress')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                activeJobs = data.data;
                const select = document.getElementById('job_id');
                select.innerHTML = '<option value="">Select Job</option>';
                
                activeJobs.forEach(job => {
                    const statusBadge = job.status === 'scheduled' ? '📅' : '⚙️';
                    select.innerHTML += `<option value="${job.id}">${statusBadge} ${escapeHtml(job.job_number)} - ${escapeHtml(job.product_code)} (${job.department_name})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading active jobs:', error));
}

// Load summary statistics
function loadSummaryStats() {
    fetch('../../api/planning/production/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('activeJobsCount').textContent = stats.active_jobs || 0;
                document.getElementById('overdueJobsCount').textContent = stats.overdue_jobs || 0;
                document.getElementById('completedTodayCount').textContent = stats.completed_today || 0;
                document.getElementById('avgCompletionRate').textContent = stats.avg_completion + '%';
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Load jobs with filters
function loadJobs() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const department = document.getElementById('departmentFilter').value;
    const assigned = document.getElementById('assignedFilter').value;
    
    let url = '../../api/planning/production/jobs.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (status) url += `status=${status}&`;
    if (department) url += `department_id=${department}&`;
    if (assigned === 'me' && currentUser) url += `assigned_to=${currentUser.id}&`;
    if (assigned === 'unassigned') url += `unassigned=1&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayJobs(data.data);
            } else {
                showAlert('Error loading jobs: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading jobs', 'error');
        });
}

// Display jobs in table
function displayJobs(jobs) {
    const tbody = document.getElementById('jobsTableBody');
    
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No jobs found</td></tr>';
        return;
    }
    
    let html = '';
    const today = new Date();
    
    jobs.forEach(job => {
        const statusBadge = getStatusBadge(job.status);
        const priorityBadge = job.priority !== 'normal' ? getPriorityBadge(job.priority) : '';
        
        // Calculate progress
        const progress = job.total_quantity > 0 
            ? Math.round((job.produced_quantity / job.total_quantity) * 100)
            : 0;
        
        const progressBarClass = progress >= 100 ? 'success' : progress >= 50 ? 'warning' : 'info';
        const progressBar = `
            <div class="progress-container">
                <div class="progress-bar progress-${progressBarClass}" style="width: ${progress}%">
                    ${progress}%
                </div>
            </div>
        `;
        
        // Check if overdue
        let dueDateDisplay = formatDate(job.scheduled_end);
        if (job.status !== 'completed' && job.status !== 'cancelled') {
            const dueDate = new Date(job.scheduled_end);
            if (dueDate < today) {
                dueDateDisplay = `<span class="badge badge-danger">${dueDateDisplay}</span>`;
            } else {
                const diffDays = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
                if (diffDays <= 2) {
                    dueDateDisplay = `<span class="badge badge-warning">${dueDateDisplay}</span>`;
                }
            }
        }
        
        html += `
            <tr>
                <td>${escapeHtml(job.job_number)} ${priorityBadge}</td>
                <td>${escapeHtml(job.product_code)}<br><small>${escapeHtml(job.product_name)}</small></td>
                <td>${escapeHtml(job.department_name)}</td>
                <td>${job.assigned_to_name ? escapeHtml(job.assigned_to_name) : '<span class="text-muted">Unassigned</span>'}</td>
                <td>${job.produced_quantity || 0} / ${job.total_quantity}</td>
                <td>${progressBar}</td>
                <td>${dueDateDisplay}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-success" onclick="viewLogs(${job.id}, '${escapeHtml(job.job_number)}')" title="View Logs">
                        <span class="icon">📊</span>
                    </button>
                    <button class="btn-action btn-warning" onclick="openStatusModalForJob(${job.id})" title="Update Status">
                        <span class="icon">🔄</span>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge badge-info">Scheduled</span>',
        'in_progress': '<span class="badge badge-warning">In Progress</span>',
        'completed': '<span class="badge badge-success">Completed</span>',
        'on_hold': '<span class="badge badge-secondary">On Hold</span>',
        'cancelled': '<span class="badge badge-danger">Cancelled</span>'
    };
    return badges[status] || status;
}

// Get priority badge
function getPriorityBadge(priority) {
    const badges = {
        'high': '<span class="badge badge-warning">⚡ High</span>',
        'urgent': '<span class="badge badge-danger">🔥 Urgent</span>'
    };
    return badges[priority] || '';
}

// Open progress modal
function openProgressModal() {
    document.getElementById('progressForm').reset();
    document.getElementById('jobDetailsSection').style.display = 'none';
    
    // Set default log time to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('log_time').value = now.toISOString().slice(0, 16);
    
    loadActiveJobsForProgress();
    document.getElementById('progressModal').classList.add('active');
}

// Close progress modal
function closeProgressModal() {
    document.getElementById('progressModal').classList.remove('active');
}

// Load job details when selected
function loadJobDetails() {
    const jobId = document.getElementById('job_id').value;
    
    if (!jobId) {
        document.getElementById('jobDetailsSection').style.display = 'none';
        return;
    }
    
    fetch(`../../api/planning/production/job-progress.php?job_id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const job = data.data;
                
                document.getElementById('jobProduct').textContent = `${job.product_code} - ${job.product_name}`;
                document.getElementById('jobQuantity').textContent = job.total_quantity;
                document.getElementById('jobProduced').textContent = job.produced_quantity || 0;
                document.getElementById('jobRemaining').textContent = job.total_quantity - (job.produced_quantity || 0);
                
                const progress = job.total_quantity > 0 
                    ? Math.round((job.produced_quantity / job.total_quantity) * 100)
                    : 0;
                document.getElementById('jobProgress').textContent = progress + '%';
                
                // Set max for quantity input
                const remaining = job.total_quantity - (job.produced_quantity || 0);
                document.getElementById('quantity_produced').max = remaining;
                
                // Show mark started checkbox only if no actual_start
                document.getElementById('mark_started').checked = !job.actual_start;
                
                document.getElementById('jobDetailsSection').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading job details', 'error');
        });
}

// Save progress
function saveProgress(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const data = {
        job_id: formData.get('job_id'),
        quantity_produced: parseInt(formData.get('quantity_produced')),
        quantity_rejected: parseInt(formData.get('quantity_rejected') || 0),
        production_notes: formData.get('production_notes'),
        log_time: formData.get('log_time'),
        mark_started: document.getElementById('mark_started').checked
    };
    
    if (!data.job_id) {
        showAlert('Please select a job', 'error');
        return;
    }
    
    fetch('../../api/planning/production/log-progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Progress logged successfully', 'success');
            closeProgressModal();
            loadJobs();
            loadSummaryStats();
            loadActiveJobsForProgress();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error logging progress', 'error');
    });
}

// Open status modal for job
function openStatusModalForJob(jobId) {
    fetch(`../../api/planning/jobs/get.php?id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const job = data.data;
                
                document.getElementById('status_job_id').value = job.id;
                document.getElementById('new_status').value = job.status;
                document.getElementById('statusJobInfo').innerHTML = `
                    <strong>${escapeHtml(job.job_number)}</strong><br>
                    ${escapeHtml(job.product_code)} - ${escapeHtml(job.product_name)}<br>
                    Department: ${escapeHtml(job.department_name)}
                `;
                
                document.getElementById('statusModal').classList.add('active');
            } else {
                showAlert('Error loading job: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading job', 'error');
        });
}

// Close status modal
function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
}

// Update job status
function updateJobStatus(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const data = {
        job_id: document.getElementById('status_job_id').value,
        status: formData.get('status'),
        notes: formData.get('notes')
    };
    
    fetch('../../api/planning/production/update-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Status updated successfully', 'success');
            closeStatusModal();
            loadJobs();
            loadSummaryStats();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating status', 'error');
    });
}

// View production logs
function viewLogs(jobId, jobNumber) {
    document.getElementById('logsJobNumber').textContent = jobNumber;
    document.getElementById('logsContent').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../../api/planning/production/logs.php?job_id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayLogs(data.data);
                document.getElementById('logsModal').classList.add('active');
            } else {
                showAlert('Error loading logs: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading logs', 'error');
        });
}

// Display production logs
function displayLogs(logs) {
    if (logs.length === 0) {
        document.getElementById('logsContent').innerHTML = '<p class="text-center">No production logs found</p>';
        return;
    }
    
    let html = `
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Logged By</th>
                    <th>Produced</th>
                    <th>Rejected</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    logs.forEach(log => {
        html += `
            <tr>
                <td>${formatDateTime(log.log_time)}</td>
                <td>${escapeHtml(log.logged_by_name)}</td>
                <td><span class="badge badge-success">${log.quantity_produced}</span></td>
                <td>${log.quantity_rejected > 0 ? `<span class="badge badge-danger">${log.quantity_rejected}</span>` : '-'}</td>
                <td>${log.production_notes ? escapeHtml(log.production_notes) : '-'}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    // Calculate totals
    const totalProduced = logs.reduce((sum, log) => sum + parseInt(log.quantity_produced), 0);
    const totalRejected = logs.reduce((sum, log) => sum + parseInt(log.quantity_rejected), 0);
    
    html += `
        <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <strong>Summary:</strong> 
            Total Produced: <span class="badge badge-success">${totalProduced}</span> | 
            Total Rejected: <span class="badge badge-danger">${totalRejected}</span> | 
            Total Entries: ${logs.length}
        </div>
    `;
    
    document.getElementById('logsContent').innerHTML = html;
}

// Close logs modal
function closeLogsModal() {
    document.getElementById('logsModal').classList.remove('active');
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

// Format date and time
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
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
