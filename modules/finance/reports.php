<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('finance.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Financial Reports';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Barron Production System</title>
    <link rel="stylesheet" href="../../assets/css/industrial.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/finance.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="page-actions">
                    <button type="button" class="btn btn-primary" onclick="exportReport()">
                        <span class="icon">📊</span> Export Report
                    </button>
                </div>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3>Report Filters</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reportType">Report Type</label>
                            <select id="reportType" class="form-control">
                                <option value="bom_costs">BOM Costs Summary</option>
                                <option value="job_costs">Job Costs Analysis</option>
                                <option value="material_usage">Material Usage Report</option>
                                <option value="labor_costs">Labor Costs Report</option>
                                <option value="defect_costs">Defect Cost Impact</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dateFrom">Date From</label>
                            <input type="date" id="dateFrom" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="dateTo">Date To</label>
                            <input type="date" id="dateTo" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="departmentFilter">Department</label>
                            <select id="departmentFilter" class="form-control">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="generateReport()">Generate Report</button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 id="reportTitle">Report Results</h3>
                </div>
                <div class="card-body">
                    <div id="reportContent">
                        <p class="text-center" style="color: #666; padding: 40px;">
                            Select report type and date range, then click "Generate Report"
                        </p>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h3>Cost Summary</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #4CAF50;">💰</div>
                            <div class="stat-details">
                                <div class="stat-value" id="totalCosts">R 0.00</div>
                                <div class="stat-label">Total Costs</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #2196F3;">📦</div>
                            <div class="stat-details">
                                <div class="stat-value" id="materialCosts">R 0.00</div>
                                <div class="stat-label">Material Costs</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #FF9800;">👷</div>
                            <div class="stat-details">
                                <div class="stat-value" id="laborCosts">R 0.00</div>
                                <div class="stat-label">Labor Costs</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #f44336;">⚠️</div>
                            <div class="stat-details">
                                <div class="stat-value" id="defectCosts">R 0.00</div>
                                <div class="stat-label">Defect Costs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/reports.js"></script>
</body>
</html>
