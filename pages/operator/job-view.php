<?php
/**
 * Barron Production Management System
 * Operator Job View - Mobile-First Interactive
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$job_number = $_GET['job'] ?? '';
?>

<style>
/* Mobile-First Job View */
.job-view {
    max-width: 100%;
    padding: 10px;
}

.job-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.job-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.job-subtitle {
    font-size: 14px;
    opacity: 0.9;
}

.progress-section {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.progress-circle {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    position: relative;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
}

.action-buttons {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 15px 10px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 100;
}

.action-btn {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
    border-radius: 10px;
}

.stage-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    margin: 5px;
}

.stage-badge.completed {
    background: #d4edda;
    color: #155724;
}

.stage-badge.in-progress {
    background: #fff3cd;
    color: #856404;
}

.stage-badge.pending {
    background: #f8d7da;
    color: #721c24;
}

.defect-alert {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 5px;
}

@media (min-width: 768px) {
    .job-view {
        max-width: 800px;
        margin: 0 auto;
    }
}
</style>

<div class="job-view">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="/pages/operator/dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Job Info Card -->
    <div class="job-info-card" id="jobInfoCard">
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="progress-section" id="progressSection">
        <!-- Will be loaded via AJAX -->
    </div>

    <!-- Stages Workflow -->
    <div class="card mb-4" id="stagesSection">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Production Stages</h5>
        </div>
        <div class="card-body" id="stagesContainer">
            <!-- Will be loaded via AJAX -->
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card mb-5" id="activitySection">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
        </div>
        <div class="card-body" id="activityContainer">
            <!-- Will be loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Action Buttons (Sticky Bottom) -->
<div class="action-buttons" id="actionButtons">
    <!-- Buttons will be dynamically generated -->
</div>

<!-- Update Quantity Modal -->
<div class="modal fade" id="updateQuantityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateQuantityForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Completed Quantity</label>
                        <input type="number" class="form-control form-control-lg" id="completedQuantityInput" 
                               min="0" required>
                        <small class="text-muted">Total: <span id="totalQuantity">0</span></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="quantityNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Stage Modal -->
<div class="modal fade" id="completeStageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Complete Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="completeStageForm">
                <div class="modal-body">
                    <p>Are you sure you want to complete this stage?</p>
                    <p class="text-muted">The job will move to the next production stage.</p>
                    <div class="mb-3">
                        <label class="form-label">Completion Notes (Optional)</label>
                        <textarea class="form-control" id="stageNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Stage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentJob = null;
const jobNumber = '<?= htmlspecialchars($job_number) ?>';

// Load job details
function loadJob() {
    if (!jobNumber) {
        alert('No job specified');
        window.location.href = '/pages/operator/dashboard.php';
        return;
    }
    
    fetch(`/api/operator/scan.php?action=scan&job_number=${encodeURIComponent(jobNumber)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentJob = data.job;
                renderJob();
            } else {
                alert('Job not found');
                window.location.href = '/pages/operator/dashboard.php';
            }
        })
        .catch(error => {
            console.error('Error loading job:', error);
            alert('Error loading job');
        });
}

// Render job details
function renderJob() {
    if (!currentJob) return;
    
    const job = currentJob;
    
    // Job Info Card
    document.getElementById('jobInfoCard').innerHTML = `
        <div class="job-title">${job.job_number}</div>
        <div class="job-subtitle">${job.order_number} - ${job.customer}</div>
        <hr style="border-color: rgba(255,255,255,0.3)">
        <div class="row text-center">
            <div class="col-4">
                <div style="font-size: 24px; font-weight: bold;">${job.quantity}</div>
                <div style="font-size: 12px;">Total Qty</div>
            </div>
            <div class="col-4">
                <div style="font-size: 24px; font-weight: bold;">${job.completed_quantity}</div>
                <div style="font-size: 12px;">Completed</div>
            </div>
            <div class="col-4">
                <div style="font-size: 24px; font-weight: bold;">${job.quantity - job.completed_quantity}</div>
                <div style="font-size: 12px;">Remaining</div>
            </div>
        </div>
    `;
    
    // Progress Section
    document.getElementById('progressSection').innerHTML = `
        <h5 class="text-center mb-3">${job.product_name}</h5>
        <div class="progress-circle mb-3">
            <svg viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#e9ecef" stroke-width="10"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke="#667eea" stroke-width="10" 
                        stroke-dasharray="${job.completion_percentage * 2.83} 283" 
                        stroke-linecap="round" transform="rotate(-90 50 50)"/>
            </svg>
            <div class="progress-text">${job.completion_percentage}%</div>
        </div>
        <div class="text-center">
            <span class="badge bg-${job.status === 'in_progress' ? 'success' : 'warning'} p-2">
                ${job.status.toUpperCase()}
            </span>
        </div>
    `;
    
    // Stages
    if (job.stages && job.stages.length > 0) {
        document.getElementById('stagesContainer').innerHTML = job.stages.map(stage => {
            let badgeClass = '';
            if (stage.status === 'completed') badgeClass = 'completed';
            else if (stage.status === 'in_progress') badgeClass = 'in-progress';
            else badgeClass = 'pending';
            
            return `
                <div class="stage-badge ${badgeClass}">
                    ${stage.status === 'completed' ? '✓' : stage.status === 'in_progress' ? '⏳' : '○'} 
                    ${stage.stage_name}
                </div>
            `;
        }).join('');
    }
    
    // Recent Activity
    if (job.recent_activity && job.recent_activity.length > 0) {
        document.getElementById('activityContainer').innerHTML = job.recent_activity.map(activity => `
            <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom">
                <div>
                    <div><strong>${activity.activity_type.replace('_', ' ')}</strong></div>
                    <small class="text-muted">${activity.details}</small>
                </div>
                <small class="text-muted">${new Date(activity.created_at).toLocaleTimeString()}</small>
            </div>
        `).join('');
    } else {
        document.getElementById('activityContainer').innerHTML = '<p class="text-muted">No recent activity</p>';
    }
    
    // Action Buttons
    renderActionButtons();
}

// Render action buttons
function renderActionButtons() {
    const isAssignedToMe = currentJob.assigned_operator === '<?= $_SESSION['username'] ?>';
    const isInProgress = currentJob.status === 'in_progress';
    
    let buttons = '';
    
    if (isAssignedToMe && isInProgress) {
        buttons = `
            <button class="btn btn-warning action-btn" onclick="showUpdateQuantityModal()">
                <i class="bi bi-pencil-square"></i> Update Quantity
            </button>
            <button class="btn btn-success action-btn" onclick="showCompleteStageModal()">
                <i class="bi bi-check-circle"></i> Complete Stage
            </button>
            <button class="btn btn-danger action-btn" onclick="reportDefect()">
                <i class="bi bi-exclamation-triangle"></i> Report Defect
            </button>
        `;
    } else if (!isAssignedToMe) {
        buttons = `
            <button class="btn btn-primary action-btn" onclick="startJob()">
                <i class="bi bi-play-circle"></i> Start Working
            </button>
        `;
    }
    
    document.getElementById('actionButtons').innerHTML = buttons;
}

// Start job
function startJob() {
    fetch('/api/operator/scan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'start',
            job_id: currentJob.id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Job started successfully!');
            loadJob();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Show update quantity modal
function showUpdateQuantityModal() {
    document.getElementById('completedQuantityInput').value = currentJob.completed_quantity;
    document.getElementById('totalQuantity').textContent = currentJob.quantity;
    new bootstrap.Modal(document.getElementById('updateQuantityModal')).show();
}

// Update quantity form
document.getElementById('updateQuantityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const completedQuantity = parseInt(document.getElementById('completedQuantityInput').value);
    const notes = document.getElementById('quantityNotes').value;
    
    fetch('/api/operator/progress.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_quantity',
            job_id: currentJob.id,
            completed_quantity: completedQuantity,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Quantity updated successfully!');
            bootstrap.Modal.getInstance(document.getElementById('updateQuantityModal')).hide();
            loadJob();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Show complete stage modal
function showCompleteStageModal() {
    new bootstrap.Modal(document.getElementById('completeStageModal')).show();
}

// Complete stage form
document.getElementById('completeStageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const notes = document.getElementById('stageNotes').value;
    
    fetch('/api/operator/progress.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'complete_stage',
            job_id: currentJob.id,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Stage completed successfully!');
            bootstrap.Modal.getInstance(document.getElementById('completeStageModal')).hide();
            loadJob();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Report defect
function reportDefect() {
    window.location.href = `/pages/operator/defect-report.php?job=${encodeURIComponent(jobNumber)}`;
}

// Initial load
loadJob();

// Auto-refresh every 30 seconds
setInterval(loadJob, 30000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
