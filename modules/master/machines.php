<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('master.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Machines Management';
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
                        <button type="button" class="btn btn-primary" onclick="openMachineModal()">
                            <span class="icon">+</span> Add Machine
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search machines..." style="max-width: 300px;">
                        <select id="departmentFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Departments</option>
                        </select>
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="down">Down</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="loadMachines()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Machine Code</th>
                                    <th>Machine Name</th>
                                    <th>Machine Number</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Last Maintenance</th>
                                    <th>Next Maintenance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="machinesTableBody">
                                <tr>
                                    <td colspan="8" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Machine Modal -->
    <div id="machineModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="modalTitle">Add Machine</h2>
                <button type="button" class="close-modal" onclick="closeMachineModal()">&times;</button>
            </div>
            <form id="machineForm">
                <div class="modal-body">
                    <input type="hidden" id="machine_id" name="machine_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="machine_code" class="form-label required">Machine Code</label>
                            <input type="text" id="machine_code" name="machine_code" class="form-control" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="machine_number" class="form-label">Machine Number</label>
                            <input type="text" id="machine_number" name="machine_number" class="form-control" maxlength="50">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="machine_name" class="form-label required">Machine Name</label>
                        <input type="text" id="machine_name" name="machine_name" class="form-control" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="department_id" class="form-label required">Department</label>
                            <select id="department_id" name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label required">Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="down">Down</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="last_maintenance_date" class="form-label">Last Maintenance Date</label>
                            <input type="date" id="last_maintenance_date" name="last_maintenance_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="next_maintenance_date" class="form-label">Next Maintenance Date</label>
                            <input type="date" id="next_maintenance_date" name="next_maintenance_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="maintenance_interval_days" class="form-label">Maintenance Interval (Days)</label>
                            <input type="number" id="maintenance_interval_days" name="maintenance_interval_days" class="form-control" min="1" max="365">
                            <small class="form-text">Automatically calculate next maintenance date</small>
                        </div>
                        <div class="form-group">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" id="purchase_date" name="purchase_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" id="model" name="model" class="form-control" maxlength="100">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="serial_number" class="form-label">Serial Number</label>
                        <input type="text" id="serial_number" name="serial_number" class="form-control" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeMachineModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Machine</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Maintenance History - <span id="historyMachineName"></span></h2>
                <button type="button" class="close-modal" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="history_machine_id">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="8" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeHistoryModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/machines.js"></script>
</body>
</html>
