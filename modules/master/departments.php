<?php
/**
 * Departments Management
 */

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requirePermission('master.view');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Barron Production Management</title>
    <link rel="stylesheet" href="../../assets/css/industrial.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/master.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="layout">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="d-flex justify-between align-center">
                    <div>
                        <h1>Departments</h1>
                        <p class="text-muted">Manage departments and production stages</p>
                    </div>
                    <?php if (hasPermission('master.create')): ?>
                    <button class="btn btn-primary" onclick="openDepartmentModal()">
                        ➕ ADD DEPARTMENT
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-4">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search departments...">
                        </div>
                        <div class="col-3">
                            <select class="form-control" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <button class="btn btn-secondary" onclick="loadDepartments()">
                                🔍 SEARCH
                            </button>
                            <button class="btn btn-outline" onclick="resetFilters()">
                                RESET
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Departments Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>CODE</th>
                                    <th>DEPARTMENT NAME</th>
                                    <th>DAILY TARGET</th>
                                    <th>WEEKLY TARGET</th>
                                    <th>MONTHLY TARGET</th>
                                    <th>STAGES</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="departmentsTable">
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <p class="text-muted">Loading...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Department Modal -->
    <div class="modal" id="departmentModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">Add Department</h3>
                    <button class="btn-close" onclick="closeDepartmentModal()">×</button>
                </div>
                <div class="modal-body">
                    <form id="departmentForm">
                        <input type="hidden" id="departmentId" name="id">
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label required" for="departmentCode">Department Code</label>
                                    <input type="text" class="form-control" id="departmentCode" name="department_code" required maxlength="50">
                                    <span class="form-text">e.g., EMBR, SCPR</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label required" for="departmentName">Department Name</label>
                                    <input type="text" class="form-control" id="departmentName" name="department_name" required maxlength="255">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label" for="dailyTarget">Daily Target</label>
                                    <input type="number" class="form-control" id="dailyTarget" name="daily_target" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label" for="weeklyTarget">Weekly Target</label>
                                    <input type="number" class="form-control" id="weeklyTarget" name="weekly_target" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label" for="monthlyTarget">Monthly Target</label>
                                    <input type="number" class="form-control" id="monthlyTarget" name="monthly_target" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="isActive" name="is_active" checked>
                                Active
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="closeDepartmentModal()">CANCEL</button>
                    <button class="btn btn-primary" onclick="saveDepartment()">SAVE</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Production Stages Modal -->
    <div class="modal" id="stagesModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="stagesModalTitle">Production Stages</h3>
                    <button class="btn-close" onclick="closeStagesModal()">×</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stagesDepartmentId">
                    
                    <button class="btn btn-sm btn-secondary mb-3" onclick="addStageRow()">
                        ➕ ADD STAGE
                    </button>
                    
                    <div class="table-wrapper">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ORDER</th>
                                    <th style="width: 120px;">CODE</th>
                                    <th>STAGE NAME</th>
                                    <th style="width: 150px;">DURATION (HRS)</th>
                                    <th style="width: 100px;">ACTIVE</th>
                                    <th style="width: 80px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="stagesTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="closeStagesModal()">CANCEL</button>
                    <button class="btn btn-primary" onclick="saveStages()">SAVE ALL</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/departments.js"></script>
</body>
</html>
