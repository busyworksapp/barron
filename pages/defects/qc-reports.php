<?php
/**
 * Barron Production Management System
 * QC Reports Dashboard
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
            <h2 class="mb-1">📊 QC Reports</h2>
            <p class="text-muted mb-0">Quality Control reports and analytics</p>
        </div>
        <button class="btn btn-primary" onclick="generateReport()">
            <i class="bi bi-file-earmark-bar-graph"></i> Generate Report
        </button>
    </div>

    <!-- Report Type Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" id="reportType" onchange="loadReport()">
                        <option value="daily">Daily Report</option>
                        <option value="weekly" selected>Weekly Report</option>
                        <option value="monthly">Monthly Report</option>
                        <option value="custom">Custom Date Range</option>
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
                    <button class="btn btn-info w-100" onclick="loadReport()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4" id="kpiCards">
        <!-- KPIs will be loaded via AJAX -->
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Trend Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📈 Defect Trend Analysis</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="80"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Severity Distribution -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">⚠️ Severity Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🏭 Department Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="departmentTable">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Defects</th>
                                    <th>Critical</th>
                                    <th>High</th>
                                    <th>Medium</th>
                                    <th>Low</th>
                                    <th>Avg Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="departmentTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Replacement Tickets Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🎫 Replacement Tickets</h5>
                </div>
                <div class="card-body">
                    <div id="ticketsSummary">
                        <!-- Tickets summary will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 Export & Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="exportToPDF()">
                            <i class="bi bi-file-pdf"></i> Export to PDF
                        </button>
                        <button class="btn btn-outline-success" onclick="exportToExcel()">
                            <i class="bi bi-file-excel"></i> Export to Excel
                        </button>
                        <button class="btn btn-outline-info" onclick="emailReport()">
                            <i class="bi bi-envelope"></i> Email Report
                        </button>
                        <?php if (checkPermission('admin.settings')): ?>
                        <button class="btn btn-outline-warning" onclick="scheduleReport()">
                            <i class="bi bi-calendar-check"></i> Schedule Automated Report
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let currentReport = null;
let trendChart = null;
let severityChart = null;

// Handle report type change
document.getElementById('reportType').addEventListener('change', function() {
    const customDateFields = document.querySelectorAll('#dateFromContainer, #dateToContainer');
    if (this.value === 'custom') {
        customDateFields.forEach(el => el.style.display = 'block');
    } else {
        customDateFields.forEach(el => el.style.display = 'none');
    }
});

// Load report
function loadReport() {
    const reportType = document.getElementById('reportType').value;
    let params = `type=${reportType}`;
    
    if (reportType === 'custom') {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        
        if (!dateFrom || !dateTo) {
            alert('Please select date range for custom report');
            return;
        }
        
        params += `&date_from=${dateFrom}&date_to=${dateTo}`;
    }
    
    fetch(`/api/defects/qc_reports.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentReport = data.report;
                renderReport();
            }
        });
}

// Render report
function renderReport() {
    if (!currentReport) return;
    
    const kpis = currentReport.kpis;
    const defects = currentReport.defects;
    const tickets = currentReport.replacement_tickets;
    
    // Render KPI cards
    document.getElementById('kpiCards').innerHTML = `
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-1">${kpis.total_defects}</h3>
                    <p class="mb-0">Total Defects</p>
                    <small>${currentReport.period.label}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3 class="mb-1">${kpis.critical_percentage}%</h3>
                    <p class="mb-0">Critical Defects</p>
                    <small>${defects.statistics.critical_defects} of ${kpis.total_defects}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-1">${kpis.resolution_rate}%</h3>
                    <p class="mb-0">Resolution Rate</p>
                    <small>Resolved + Closed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h3 class="mb-1">${kpis.tickets_pending_approval}</h3>
                    <p class="mb-0">Pending Approvals</p>
                    <small>${kpis.approval_rate}% approval rate</small>
                </div>
            </div>
        </div>
    `;
    
    // Render trend chart
    renderTrendChart(defects.trend);
    
    // Render severity chart
    renderSeverityChart(defects.statistics);
    
    // Render department breakdown
    renderDepartmentBreakdown(defects.by_department);
    
    // Render tickets summary
    renderTicketsSummary(tickets.statistics);
}

// Render trend chart
function renderTrendChart(trendData) {
    const ctx = document.getElementById('trendChart');
    
    if (trendChart) {
        trendChart.destroy();
    }
    
    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date_label),
            datasets: [{
                label: 'Total Defects',
                data: trendData.map(d => d.total_defects),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Critical Defects',
                data: trendData.map(d => d.critical_defects),
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Render severity chart
function renderSeverityChart(stats) {
    const ctx = document.getElementById('severityChart');
    
    if (severityChart) {
        severityChart.destroy();
    }
    
    severityChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                data: [
                    stats.critical_defects,
                    stats.high_defects,
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

// Render department breakdown
function renderDepartmentBreakdown(departments) {
    const tbody = document.getElementById('departmentTableBody');
    
    if (departments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = departments.map(dept => `
        <tr>
            <td><strong>${dept.department_name}</strong></td>
            <td>${dept.total_defects}</td>
            <td><span class="badge bg-danger">${dept.critical || 0}</span></td>
            <td><span class="badge bg-warning">${dept.high || 0}</span></td>
            <td><span class="badge bg-info">${dept.medium || 0}</span></td>
            <td><span class="badge bg-success">${dept.low || 0}</span></td>
            <td>${dept.avg_quantity ? Math.round(dept.avg_quantity) : 0}</td>
        </tr>
    `).join('');
}

// Render tickets summary
function renderTicketsSummary(stats) {
    document.getElementById('ticketsSummary').innerHTML = `
        <table class="table">
            <tr>
                <th>Total Tickets:</th>
                <td>${stats.total_tickets}</td>
            </tr>
            <tr>
                <th>Pending Approval:</th>
                <td><span class="badge bg-warning">${stats.pending_approval}</span></td>
            </tr>
            <tr>
                <th>Approved:</th>
                <td><span class="badge bg-success">${stats.approved}</span></td>
            </tr>
            <tr>
                <th>Rejected:</th>
                <td><span class="badge bg-danger">${stats.rejected}</span></td>
            </tr>
            <tr>
                <th>No Stock:</th>
                <td><span class="badge bg-danger">${stats.no_stock}</span></td>
            </tr>
            <tr>
                <th>Completed:</th>
                <td><span class="badge bg-success">${stats.completed}</span></td>
            </tr>
        </table>
    `;
}

// Export functions
function exportToPDF() {
    alert('PDF export will be implemented in Phase 10');
}

function exportToExcel() {
    alert('Excel export will be implemented in Phase 10');
}

function emailReport() {
    alert('Email report will be implemented in Phase 9 (Notifications)');
}

function scheduleReport() {
    alert('Report scheduling will be implemented in Phase 9 (Notifications)');
}

function generateReport() {
    loadReport();
}

// Initial load
loadReport();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
