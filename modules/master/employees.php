<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('master.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Employees Management';
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
    <link rel="stylesheet" href="../../assets/css/master.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="page-actions">
                    <?php if (hasPermission('master.edit')): ?>
                        <button type="button" class="btn btn-primary" onclick="openEmployeeModal()">
                            <span class="icon">+</span> Add Employee
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search employees..." style="max-width: 300px;">
                        <select id="departmentFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Departments</option>
                        </select>
                        <select id="roleFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Roles</option>
                        </select>
                        <select id="statusFilter" class="form-control" style="max-width: 150px;">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="loadEmployees()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee #</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Primary Department</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="employeesTableBody">
                                <tr>
                                    <td colspan="9" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Employee Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="modalTitle">Add Employee</h2>
                <button type="button" class="close-modal" onclick="closeEmployeeModal()">&times;</button>
            </div>
            <form id="employeeForm">
                <div class="modal-body">
                    <input type="hidden" id="employee_id" name="employee_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employee_number" class="form-label required">Employee Number</label>
                            <input type="text" id="employee_number" name="employee_number" class="form-control" required maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="role_id" class="form-label required">Role</label>
                            <select id="role_id" name="role_id" class="form-control" required>
                                <option value="">Select Role</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label required">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label required">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required maxlength="50">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" maxlength="20">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" readonly style="background-color: #f5f5f5;">
                            <small class="form-text">Auto-generated as firstname@barron</small>
                        </div>
                        <div class="form-group">
                            <label for="primary_department_id" class="form-label required">Primary Department</label>
                            <select id="primary_department_id" name="primary_department_id" class="form-control" required>
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="passwordSection">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="6">
                        <small class="form-text">Leave blank to keep existing password (for updates) or use employee number as default</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Additional Departments</label>
                        <div id="additionalDepartments" class="checkbox-group">
                            <!-- Dynamically loaded checkboxes -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEmployeeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Departments Modal -->
    <div id="departmentsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Manage Departments - <span id="deptEmployeeName"></span></h2>
                <button type="button" class="close-modal" onclick="closeDepartmentsModal()">&times;</button>
            </div>
            <form id="departmentsForm">
                <div class="modal-body">
                    <input type="hidden" id="dept_employee_id" name="employee_id">
                    <div class="form-group">
                        <label class="form-label">Assign to Departments</label>
                        <div id="departmentsList" class="checkbox-group">
                            <!-- Dynamically loaded checkboxes -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDepartmentsModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Departments</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/employees.js"></script>
</body>
</html>
