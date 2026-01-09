<?php
/**
 * Barron Production Management System
 * Capacity Planning View
 */

require_once __DIR__ . '/../../includes/auth_check.php';

// Check permissions
if (!checkPermission('planning.view')) {
    header('Location: /pages/dashboard.php?error=access_denied');
    exit;
}

$page_title = 'Capacity Planning';
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Capacity Planning</h1>
    <div class="header-actions">
        <button class="btn btn-secondary" onclick="exportCapacityReport()">
            <i class="fas fa-download"></i> Export Report
        </button>
    </div>
</div>

<!-- Date Range Selector -->
<div class="card">
    <div class="card-body">
        <div class="date-range-selector">
            <div class="form-group">
                <label>Date From</label>
                <input type="date" id="dateFrom" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" id="dateTo" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
            </div>
            <button class="btn btn-primary" onclick="loadCapacityData()">
                <i class="fas fa-sync"></i> Load Data
            </button>
        </div>
    </div>
</div>

<!-- Capacity Overview -->
<div class="card mt-20">
    <div class="card-header">
        <h3>Departments Capacity Overview</h3>
    </div>
    <div class="card-body">
        <div id="capacityOverview">
            <div class="loading-spinner"></div>
            Loading capacity data...
        </div>
    </div>
</div>

<!-- Department Details (expandable) -->
<div id="departmentDetails" class="mt-20"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCapacityData();
});

function loadCapacityData() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    fetch(`/api/planning/capacity.php?action=overview&date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCapacityOverview(data.departments);
            }
        })
        .catch(error => {
            console.error('Error loading capacity:', error);
            showAlert('Failed to load capacity data', 'error');
        });
}

function renderCapacityOverview(departments) {
    const container = document.getElementById('capacityOverview');
    
    if (departments.length === 0) {
        container.innerHTML = '<p class="text-muted">No capacity data available for selected date range</p>';
        return;
    }
    
    let html = '<div class="capacity-cards">';
    
    departments.forEach(dept => {
        const utilization = dept.utilization_percent;
        const status = dept.status;
        
        html += `
            <div class="capacity-card">
                <div class="capacity-card-header">
                    <h4>${dept.department_name}</h4>
                    <span class="capacity-badge capacity-${status}">${utilization}%</span>
                </div>
                <div class="capacity-visual">
                    <div class="capacity-bar">
                        <div class="capacity-fill capacity-${status}" style="width: ${Math.min(utilization, 100)}%">
                            ${utilization > 100 ? '<span class="overflow-indicator">!' + Math.round(utilization - 100) + '%</span>' : ''}
                        </div>
                    </div>
                    <div class="capacity-labels">
                        <span>0%</span>
                        <span>50%</span>
                        <span>100%</span>
                    </div>
                </div>
                <div class="capacity-stats">
                    <div class="stat">
                        <label>Total Capacity</label>
                        <value>${dept.total_capacity}</value>
                    </div>
                    <div class="stat">
                        <label>Total Jobs</label>
                        <value>${dept.total_jobs}</value>
                    </div>
                    <div class="stat">
                        <label>Available</label>
                        <value>${dept.available_capacity}</value>
                    </div>
                </div>
                <div class="capacity-breakdown">
                    <div class="breakdown-item">
                        <span class="badge badge-scheduled"></span>
                        <label>Scheduled</label>
                        <value>${dept.scheduled_jobs || 0}</value>
                    </div>
                    <div class="breakdown-item">
                        <span class="badge badge-in_progress"></span>
                        <label>In Progress</label>
                        <value>${dept.in_progress_jobs || 0}</value>
                    </div>
                    <div class="breakdown-item">
                        <span class="badge badge-completed"></span>
                        <label>Completed</label>
                        <value>${dept.completed_jobs || 0}</value>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline mt-10" onclick="viewDepartmentDetails(${dept.id}, '${dept.department_name}')">
                    <i class="fas fa-chart-line"></i> View Details
                </button>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function viewDepartmentDetails(deptId, deptName) {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    fetch(`/api/planning/capacity.php?action=department&department_id=${deptId}&date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDepartmentDetails(deptName, data.capacity);
            }
        })
        .catch(error => {
            console.error('Error loading department details:', error);
            showAlert('Failed to load department details', 'error');
        });
}

function renderDepartmentDetails(deptName, capacity) {
    const container = document.getElementById('departmentDetails');
    
    let html = `
        <div class="card">
            <div class="card-header">
                <h3>${deptName} - Daily Capacity Details</h3>
                <button class="btn-close" onclick="closeDepartmentDetails()">&times;</button>
            </div>
            <div class="card-body">
    `;
    
    if (capacity.daily_capacity && capacity.daily_capacity.length > 0) {
        html += '<div class="daily-capacity-chart">';
        
        capacity.daily_capacity.forEach(day => {
            const date = new Date(day.date);
            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const utilization = day.utilization_percent;
            const status = day.status;
            
            html += `
                <div class="daily-bar">
                    <div class="bar-container">
                        <div class="bar bar-${status}" style="height: ${Math.min(utilization, 100)}%" title="${utilization}% utilized">
                            ${utilization > 100 ? '<span class="overflow-text">!' + Math.round(utilization - 100) + '%</span>' : ''}
                        </div>
                    </div>
                    <div class="bar-label">${dateStr}</div>
                    <div class="bar-info">${day.job_count} jobs</div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Summary
        const summary = capacity.summary;
        html += `
            <div class="capacity-summary mt-20">
                <h4>Period Summary</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Total Days</label>
                        <value>${summary.total_days}</value>
                    </div>
                    <div class="summary-item">
                        <label>Avg Utilization</label>
                        <value>${summary.avg_utilization}%</value>
                    </div>
                    <div class="summary-item alert">
                        <label>Overbooked Days</label>
                        <value>${summary.overbooked_days}</value>
                    </div>
                    <div class="summary-item warning">
                        <label>High Utilization Days</label>
                        <value>${summary.high_utilization_days}</value>
                    </div>
                    <div class="summary-item success">
                        <label>Normal Days</label>
                        <value>${summary.normal_days}</value>
                    </div>
                </div>
            </div>
        `;
    } else {
        html += '<p class="text-muted">No daily capacity data available</p>';
    }
    
    html += '</div></div>';
    container.innerHTML = html;
    container.scrollIntoView({ behavior: 'smooth' });
}

function closeDepartmentDetails() {
    document.getElementById('departmentDetails').innerHTML = '';
}

function exportCapacityReport() {
    showAlert('Export functionality coming soon', 'info');
}
</script>

<style>
.date-range-selector {
    display: flex;
    gap: 15px;
    align-items: end;
}

.capacity-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.capacity-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
}

.capacity-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.capacity-card-header h4 {
    margin: 0;
    font-size: 18px;
}

.capacity-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 16px;
}

.capacity-badge.capacity-normal {
    background: #d1fae5;
    color: #065f46;
}

.capacity-badge.capacity-high {
    background: #fed7aa;
    color: #92400e;
}

.capacity-badge.capacity-overbooked {
    background: #fecaca;
    color: #991b1b;
}

.capacity-visual {
    margin-bottom: 20px;
}

.capacity-bar {
    height: 40px;
    background: #f3f4f6;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
}

.capacity-fill {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 10px;
    transition: width 0.5s ease;
    position: relative;
}

.capacity-fill.capacity-normal {
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.capacity-fill.capacity-high {
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.capacity-fill.capacity-overbooked {
    background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
}

.overflow-indicator,
.overflow-text {
    background: rgba(255,255,255,0.3);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    color: white;
}

.capacity-labels {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.capacity-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    padding: 15px 0;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}

.capacity-stats .stat {
    text-align: center;
}

.capacity-stats label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.capacity-stats value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.capacity-breakdown {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}

.breakdown-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.breakdown-item .badge {
    width: 30px;
    height: 30px;
    border-radius: 6px;
}

.breakdown-item label {
    font-size: 11px;
    color: #666;
}

.breakdown-item value {
    font-size: 16px;
    font-weight: 700;
    color: #333;
}

.daily-capacity-chart {
    display: flex;
    gap: 10px;
    padding: 20px 0;
    overflow-x: auto;
}

.daily-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 60px;
}

.bar-container {
    height: 150px;
    width: 40px;
    background: #f3f4f6;
    border-radius: 6px 6px 0 0;
    display: flex;
    align-items: flex-end;
    position: relative;
}

.bar {
    width: 100%;
    border-radius: 6px 6px 0 0;
    transition: height 0.5s ease;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 5px;
}

.bar.bar-normal {
    background: #10b981;
}

.bar.bar-high {
    background: #f59e0b;
}

.bar.bar-overbooked {
    background: #ef4444;
}

.bar-label {
    font-size: 12px;
    font-weight: 600;
    margin-top: 5px;
}

.bar-info {
    font-size: 11px;
    color: #666;
}

.capacity-summary {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.summary-item {
    background: white;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
}

.summary-item.alert {
    border-left: 4px solid #ef4444;
}

.summary-item.warning {
    border-left: 4px solid #f59e0b;
}

.summary-item.success {
    border-left: 4px solid #10b981;
}

.summary-item label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.summary-item value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

@media (max-width: 768px) {
    .capacity-cards {
        grid-template-columns: 1fr;
    }
    
    .date-range-selector {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
