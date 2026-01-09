<?php
/**
 * Barron Production Management System
 * Defect Analytics Page
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
            <h2 class="mb-1">📊 Defect Analytics</h2>
            <p class="text-muted mb-0">Deep dive into defect patterns and trends</p>
        </div>
        <a href="/pages/defects/list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Defects
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Time Period</label>
                    <select class="form-select" id="periodSelector" onchange="handlePeriodChange()">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3" id="dateFromContainer" style="display: none;">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-3" id="dateToContainer" style="display: none;">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" id="dateTo">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="loadAnalytics()">
                        <i class="bi bi-bar-chart"></i> Load Analytics
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summaryCards">
        <!-- Cards will be loaded via AJAX -->
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <!-- Trend Over Time -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📈 Defect Trend Over Time</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="setTrendPeriod('day')">Daily</button>
                        <button type="button" class="btn btn-outline-primary active" onclick="setTrendPeriod('week')">Weekly</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="60"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Severity Distribution -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">⚠️ Severity Breakdown</h5>
                </div>
                <div class="card-body">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">📋 Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Department Performance -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">🏭 Top Departments</h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Tables -->
    <div class="row">
        <!-- Department Breakdown -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🏭 Department Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Defects</th>
                                    <th>Critical %</th>
                                    <th>Avg Qty</th>
                                </tr>
                            </thead>
                            <tbody id="departmentTableBody">
                                <tr><td colspan="4" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📊 Key Performance Indicators</h5>
                </div>
                <div class="card-body" id="kpiMetrics">
                    <!-- KPIs will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let trendChart = null;
let severityChart = null;
let statusChart = null;
let departmentChart = null;
let currentTrendPeriod = 'week';
let analyticsData = null;

// Handle period selector
function handlePeriodChange() {
    const period = document.getElementById('periodSelector').value;
    const customFields = document.querySelectorAll('#dateFromContainer, #dateToContainer');
    
    if (period === 'custom') {
        customFields.forEach(el => el.style.display = 'block');
    } else {
        customFields.forEach(el => el.style.display = 'none');
    }
}

// Get date range
function getDateRange() {
    const period = document.getElementById('periodSelector').value;
    let dateFrom, dateTo;
    
    if (period === 'custom') {
        dateFrom = document.getElementById('dateFrom').value;
        dateTo = document.getElementById('dateTo').value;
        
        if (!dateFrom || !dateTo) {
            alert('Please select date range');
            return null;
        }
    } else {
        const days = parseInt(period);
        dateTo = new Date();
        dateFrom = new Date();
        dateFrom.setDate(dateFrom.getDate() - days);
        
        dateFrom = dateFrom.toISOString().split('T')[0];
        dateTo = dateTo.toISOString().split('T')[0];
    }
    
    return { dateFrom, dateTo };
}

// Load analytics
async function loadAnalytics() {
    const dateRange = getDateRange();
    if (!dateRange) return;
    
    try {
        // Load statistics
        const statsResponse = await fetch(`/api/defects/defects.php?action=statistics&date_from=${dateRange.dateFrom}&date_to=${dateRange.dateTo}`);
        const statsData = await statsResponse.json();
        
        // Load department breakdown
        const deptResponse = await fetch(`/api/defects/defects.php?action=by_department&date_from=${dateRange.dateFrom}&date_to=${dateRange.dateTo}`);
        const deptData = await deptResponse.json();
        
        // Load trend
        const trendResponse = await fetch(`/api/defects/defects.php?action=trend&period=${currentTrendPeriod}`);
        const trendData = await trendResponse.json();
        
        if (statsData.success && deptData.success && trendData.success) {
            analyticsData = {
                statistics: statsData.statistics,
                departments: deptData.departments,
                trend: trendData.trend
            };
            
            renderAnalytics();
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        alert('Failed to load analytics');
    }
}

// Render analytics
function renderAnalytics() {
    if (!analyticsData) return;
    
    const stats = analyticsData.statistics;
    const departments = analyticsData.departments;
    const trend = analyticsData.trend;
    
    // Render summary cards
    document.getElementById('summaryCards').innerHTML = `
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-primary">${stats.total_defects}</h2>
                    <p class="mb-0">Total Defects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-danger">${stats.critical_defects}</h2>
                    <p class="mb-0">Critical Defects</p>
                    <small class="text-muted">${stats.total_defects > 0 ? Math.round((stats.critical_defects / stats.total_defects) * 100) : 0}% of total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-success">${Math.round(((stats.resolved_defects + stats.closed_defects) / Math.max(stats.total_defects, 1)) * 100)}%</h2>
                    <p class="mb-0">Resolution Rate</p>
                    <small class="text-muted">${stats.resolved_defects + stats.closed_defects} resolved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-info">${stats.quantity_affected}</h2>
                    <p class="mb-0">Units Affected</p>
                    <small class="text-muted">Across all defects</small>
                </div>
            </div>
        </div>
    `;
    
    // Render charts
    renderTrendChart(trend);
    renderSeverityChart(stats);
    renderStatusChart(stats);
    renderDepartmentChart(departments);
    
    // Render tables
    renderDepartmentTable(departments);
    renderKPIs(stats, departments);
}

// Render trend chart
function renderTrendChart(trendData) {
    const ctx = document.getElementById('trendChart');
    
    if (trendChart) trendChart.destroy();
    
    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date_label),
            datasets: [
                {
                    label: 'Total Defects',
                    data: trendData.map(d => d.total_defects),
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Critical',
                    data: trendData.map(d => d.critical_defects),
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Resolved',
                    data: trendData.map(d => d.resolved_defects),
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Render severity chart
function renderSeverityChart(stats) {
    const ctx = document.getElementById('severityChart');
    
    if (severityChart) severityChart.destroy();
    
    severityChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                data: [
                    stats.critical_defects,
                    stats.high_defects || 0,
                    stats.medium_defects || 0,
                    stats.low_defects || 0
                ],
                backgroundColor: [
                    'rgb(220, 53, 69)',
                    'rgb(255, 193, 7)',
                    'rgb(13, 202, 240)',
                    'rgb(25, 135, 84)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Render status chart
function renderStatusChart(stats) {
    const ctx = document.getElementById('statusChart');
    
    if (statusChart) statusChart.destroy();
    
    statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
            datasets: [{
                data: [
                    stats.open_defects,
                    stats.in_progress_defects,
                    stats.resolved_defects,
                    stats.closed_defects
                ],
                backgroundColor: [
                    'rgb(220, 53, 69)',
                    'rgb(255, 193, 7)',
                    'rgb(13, 202, 240)',
                    'rgb(25, 135, 84)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Render department chart
function renderDepartmentChart(departments) {
    const ctx = document.getElementById('departmentChart');
    
    if (departmentChart) departmentChart.destroy();
    
    const topDepts = departments.slice(0, 5); // Top 5 departments
    
    departmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topDepts.map(d => d.department_name),
            datasets: [{
                label: 'Defects',
                data: topDepts.map(d => d.total_defects),
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgb(13, 110, 253)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Render department table
function renderDepartmentTable(departments) {
    const tbody = document.getElementById('departmentTableBody');
    
    if (departments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = departments.map(dept => {
        const criticalPercent = dept.total_defects > 0 
            ? Math.round(((dept.critical || 0) / dept.total_defects) * 100) 
            : 0;
        
        return `
            <tr>
                <td><strong>${dept.department_name}</strong></td>
                <td>${dept.total_defects}</td>
                <td><span class="badge bg-${criticalPercent > 30 ? 'danger' : criticalPercent > 15 ? 'warning' : 'success'}">${criticalPercent}%</span></td>
                <td>${dept.avg_quantity ? Math.round(dept.avg_quantity) : 0}</td>
            </tr>
        `;
    }).join('');
}

// Render KPIs
function renderKPIs(stats, departments) {
    const totalDepts = departments.length;
    const avgDefectsPerDept = totalDepts > 0 ? Math.round(stats.total_defects / totalDepts) : 0;
    const replacementRate = stats.total_defects > 0 
        ? Math.round((stats.replacement_tickets / stats.total_defects) * 100) 
        : 0;
    
    document.getElementById('kpiMetrics').innerHTML = `
        <table class="table table-sm">
            <tr>
                <th>Avg Defects per Department:</th>
                <td>${avgDefectsPerDept}</td>
            </tr>
            <tr>
                <th>Replacement Ticket Rate:</th>
                <td>${replacementRate}%</td>
            </tr>
            <tr>
                <th>Active Defects:</th>
                <td>${stats.open_defects + stats.in_progress_defects}</td>
            </tr>
            <tr>
                <th>Total Quantity Affected:</th>
                <td>${stats.quantity_affected}</td>
            </tr>
            <tr>
                <th>Avg Quantity per Defect:</th>
                <td>${stats.total_defects > 0 ? Math.round(stats.quantity_affected / stats.total_defects) : 0}</td>
            </tr>
        </table>
    `;
}

// Set trend period
function setTrendPeriod(period) {
    currentTrendPeriod = period;
    
    // Update button states
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    loadAnalytics();
}

// Initial load
loadAnalytics();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
