<?php
/**
 * Barron Production Management System
 * Operator Defect Report - Quick Reporting
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$job_number = $_GET['job'] ?? '';
?>

<style>
.defect-report {
    max-width: 100%;
    padding: 10px;
}

.severity-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
}

.severity-btn {
    padding: 20px;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    background: white;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s;
}

.severity-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.severity-btn.active {
    border-width: 3px;
}

.severity-btn.critical.active {
    border-color: #dc3545;
    background: #f8d7da;
}

.severity-btn.high.active {
    border-color: #fd7e14;
    background: #fff3cd;
}

.severity-btn.medium.active {
    border-color: #0dcaf0;
    background: #cff4fc;
}

.severity-btn.low.active {
    border-color: #198754;
    background: #d1e7dd;
}

.severity-icon {
    font-size: 32px;
    margin-bottom: 5px;
}

.severity-label {
    font-size: 14px;
    font-weight: bold;
}

.submit-btn {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    font-weight: bold;
}

@media (min-width: 768px) {
    .defect-report {
        max-width: 800px;
        margin: 0 auto;
    }
}
</style>

<div class="defect-report">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="/pages/operator/job-view.php?job=<?= urlencode($job_number) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Job
        </a>
    </div>

    <!-- Page Header -->
    <div class="card mb-4 bg-warning">
        <div class="card-body text-center">
            <h3 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Report Defect</h3>
            <p class="mb-0">Job: <strong id="displayJobNumber"><?= htmlspecialchars($job_number) ?></strong></p>
        </div>
    </div>

    <!-- Defect Form -->
    <form id="defectReportForm">
        <input type="hidden" id="jobId" name="job_id">
        
        <!-- Severity Selection -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">1. Select Severity Level *</h5>
            </div>
            <div class="card-body">
                <div class="severity-selector">
                    <div class="severity-btn critical" onclick="selectSeverity('critical')">
                        <div class="severity-icon">🔴</div>
                        <div class="severity-label">CRITICAL</div>
                        <small>Production stopped</small>
                    </div>
                    <div class="severity-btn high" onclick="selectSeverity('high')">
                        <div class="severity-icon">🟠</div>
                        <div class="severity-label">HIGH</div>
                        <small>Significant impact</small>
                    </div>
                    <div class="severity-btn medium" onclick="selectSeverity('medium')">
                        <div class="severity-icon">🟡</div>
                        <div class="severity-label">MEDIUM</div>
                        <small>Moderate impact</small>
                    </div>
                    <div class="severity-btn low" onclick="selectSeverity('low')">
                        <div class="severity-icon">🟢</div>
                        <div class="severity-label">LOW</div>
                        <small>Minor impact</small>
                    </div>
                </div>
                <input type="hidden" id="severityInput" name="severity" required>
            </div>
        </div>

        <!-- Quantity -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">2. Affected Quantity *</h5>
            </div>
            <div class="card-body">
                <input type="number" class="form-control form-control-lg" name="quantity" 
                       placeholder="Number of defective units" min="1" required>
            </div>
        </div>

        <!-- Description -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">3. Describe the Defect *</h5>
            </div>
            <div class="card-body">
                <textarea class="form-control" name="description" rows="4" 
                          placeholder="What is wrong? When did you notice it?" required></textarea>
            </div>
        </div>

        <!-- Replacement Required -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">4. Replacement Required?</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="requiresReplacement" 
                           name="requires_replacement" value="1">
                    <label class="form-check-label" for="requiresReplacement">
                        Yes, this defect requires replacement units
                    </label>
                </div>
                <small class="text-muted">A replacement ticket will be created and sent for manager approval</small>
            </div>
        </div>

        <!-- Photo Upload (Placeholder for Phase 10) -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">5. Photo Evidence (Optional)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-camera"></i> Photo upload feature will be available in Phase 10
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-danger submit-btn">
            <i class="bi bi-send"></i> REPORT DEFECT
        </button>
    </form>
</div>

<script>
const jobNumber = '<?= htmlspecialchars($job_number) ?>';
let selectedSeverity = null;

// Load job details
function loadJobDetails() {
    if (!jobNumber) {
        alert('No job specified');
        window.location.href = '/pages/operator/dashboard.php';
        return;
    }
    
    fetch(`/api/operator/scan.php?action=scan&job_number=${encodeURIComponent(jobNumber)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('jobId').value = data.job.id;
            } else {
                alert('Job not found');
                window.location.href = '/pages/operator/dashboard.php';
            }
        });
}

// Select severity
function selectSeverity(severity) {
    // Remove active from all
    document.querySelectorAll('.severity-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active to selected
    document.querySelector(`.severity-btn.${severity}`).classList.add('active');
    
    // Set value
    selectedSeverity = severity;
    document.getElementById('severityInput').value = severity;
}

// Form submission
document.getElementById('defectReportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedSeverity) {
        alert('Please select a severity level');
        return;
    }
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Convert checkbox to boolean
    data.requires_replacement = formData.get('requires_replacement') ? 1 : 0;
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Reporting...';
    submitBtn.disabled = true;
    
    fetch('/api/operator/defect.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Defect reported successfully!\n\n' + data.message);
            window.location.href = `/pages/operator/job-view.php?job=${encodeURIComponent(jobNumber)}`;
        } else {
            alert('Error: ' + data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to report defect');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Initial load
loadJobDetails();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
