<?php
/**
 * Barron Production Management System
 * Operator Dashboard - Mobile-First
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Mobile-First Operator UI */
.operator-dashboard {
    max-width: 100%;
    padding: 10px;
}

.scan-button {
    width: 100%;
    height: 80px;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.scan-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-value {
    font-size: 48px;
    font-weight: bold;
    color: #667eea;
    margin: 10px 0;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
    text-transform: uppercase;
}

.job-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
}

.job-card.priority-urgent {
    border-left-color: #dc3545;
}

.job-card.priority-high {
    border-left-color: #fd7e14;
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.job-number {
    font-size: 18px;
    font-weight: bold;
    color: #212529;
}

.progress-ring {
    width: 60px;
    height: 60px;
}

.quick-action-btn {
    width: 100%;
    margin-top: 10px;
    padding: 12px;
    font-size: 16px;
}

@media (min-width: 768px) {
    .operator-dashboard {
        max-width: 800px;
        margin: 0 auto;
    }
}
</style>

<div class="operator-dashboard">
    <!-- Scan Button -->
    <button class="btn btn-primary scan-button" onclick="showScanModal()">
        <i class="bi bi-qr-code-scan"></i> SCAN JOB
    </button>

    <!-- Statistics -->
    <div class="row mb-4" id="statsContainer">
        <!-- Stats will be loaded via AJAX -->
    </div>

    <!-- My Active Jobs -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-briefcase"></i> My Active Jobs</h5>
        </div>
        <div class="card-body" id="myJobsContainer">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Jobs -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-list-task"></i> Available Jobs</h5>
        </div>
        <div class="card-body" id="availableJobsContainer">
            <div class="text-center">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scan Modal -->
<div class="modal fade" id="scanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Scan Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Enter Job Number</label>
                    <input type="text" class="form-control form-control-lg" id="jobNumberInput" 
                           placeholder="JOB-XXXXXXXX" autofocus>
                </div>
                <button class="btn btn-primary btn-lg w-100" onclick="scanJob()">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let userDepartmentId = <?= $_SESSION['department_id'] ?? 'null' ?>;

// Load statistics
function loadStats() {
    fetch('/api/operator/scan.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.statistics;
                document.getElementById('statsContainer').innerHTML = `
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-value">${stats.jobs_completed}</div>
                            <div class="stat-label">Jobs Completed</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-value">${stats.active_jobs}</div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-value">${stats.stages_completed}</div>
                            <div class="stat-label">Stages Done</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-value">${stats.defects_reported}</div>
                            <div class="stat-label">Defects Found</div>
                        </div>
                    </div>
                `;
            }
        });
}

// Load my jobs
function loadMyJobs() {
    fetch('/api/operator/scan.php?action=my_jobs')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderJobs(data.jobs, 'myJobsContainer', true);
            }
        });
}

// Load available jobs
function loadAvailableJobs() {
    if (!userDepartmentId) {
        document.getElementById('availableJobsContainer').innerHTML = 
            '<p class="text-muted">No department assigned</p>';
        return;
    }
    
    fetch(`/api/operator/scan.php?action=available&department_id=${userDepartmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderJobs(data.jobs, 'availableJobsContainer', false);
            }
        });
}

// Render jobs
function renderJobs(jobs, containerId, isMyJobs) {
    const container = document.getElementById(containerId);
    
    if (jobs.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No jobs available</p>';
        return;
    }
    
    container.innerHTML = jobs.map(job => {
        const progress = job.quantity > 0 ? Math.round((job.completed_quantity / job.quantity) * 100) : 0;
        const priorityClass = job.priority === 'urgent' ? 'priority-urgent' : 
                             job.priority === 'high' ? 'priority-high' : '';
        
        return `
            <div class="job-card ${priorityClass}">
                <div class="job-header">
                    <div>
                        <div class="job-number">${job.job_number}</div>
                        <small class="text-muted">${job.order_number}</small>
                    </div>
                    <div>
                        <span class="badge bg-${job.priority === 'urgent' ? 'danger' : job.priority === 'high' ? 'warning' : 'info'}">
                            ${job.priority}
                        </span>
                    </div>
                </div>
                <div class="mb-2">
                    <strong>${job.product_name}</strong>
                    <div class="text-muted">${job.current_stage_name}</div>
                </div>
                <div class="progress mb-2" style="height: 25px;">
                    <div class="progress-bar" role="progressbar" style="width: ${progress}%" 
                         aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100">
                        ${job.completed_quantity}/${job.quantity} (${progress}%)
                    </div>
                </div>
                ${isMyJobs ? `
                    <button class="btn btn-success quick-action-btn" onclick="viewJob('${job.job_number}')">
                        <i class="bi bi-play-fill"></i> Continue Working
                    </button>
                ` : `
                    <button class="btn btn-primary quick-action-btn" onclick="startJob(${job.id}, '${job.job_number}')">
                        <i class="bi bi-play-circle"></i> Start Job
                    </button>
                `}
            </div>
        `;
    }).join('');
}

// Show scan modal
function showScanModal() {
    const modal = new bootstrap.Modal(document.getElementById('scanModal'));
    modal.show();
    
    // Focus input after modal is shown
    setTimeout(() => {
        document.getElementById('jobNumberInput').focus();
    }, 500);
}

// Scan job
function scanJob() {
    const jobNumber = document.getElementById('jobNumberInput').value.trim();
    
    if (!jobNumber) {
        alert('Please enter a job number');
        return;
    }
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('scanModal')).hide();
    
    // Navigate to job view
    window.location.href = `/pages/operator/job-view.php?job=${encodeURIComponent(jobNumber)}`;
}

// View job
function viewJob(jobNumber) {
    window.location.href = `/pages/operator/job-view.php?job=${encodeURIComponent(jobNumber)}`;
}

// Start job
function startJob(jobId, jobNumber) {
    if (!confirm('Start working on this job?')) return;
    
    fetch('/api/operator/scan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'start',
            job_id: jobId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Navigate to job view
            window.location.href = `/pages/operator/job-view.php?job=${encodeURIComponent(jobNumber)}`;
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Handle enter key in scan input
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('jobNumberInput');
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                scanJob();
            }
        });
    }
});

// Auto-refresh every 30 seconds
setInterval(() => {
    loadMyJobs();
    loadAvailableJobs();
}, 30000);

// Initial load
loadStats();
loadMyJobs();
loadAvailableJobs();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
