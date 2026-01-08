<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('planning.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Job Scheduling';
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
                    <?php if (hasPermission('planning.edit')): ?>
                        <button type="button" class="btn btn-primary" onclick="openJobModal()">
                            <span class="icon">+</span> Schedule Job
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search jobs..." style="max-width: 300px;">
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select id="departmentFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Departments</option>
                        </select>
                        <input type="date" id="dateFromFilter" class="form-control" style="max-width: 180px;" placeholder="From Date">
                        <button type="button" class="btn btn-secondary" onclick="loadJobs()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Job #</th>
                                    <th>Order</th>
                                    <th>Product</th>
                                    <th>Department</th>
                                    <th>Quantity</th>
                                    <th>Assigned To</th>
                                    <th>Start Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="jobsTableBody">
                                <tr>
                                    <td colspan="10" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Job Modal -->
    <div id="jobModal" class="modal">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h2 id="modalTitle">Schedule Job</h2>
                <button type="button" class="close-modal" onclick="closeJobModal()">&times;</button>
            </div>
            <form id="jobForm">
                <div class="modal-body">
                    <input type="hidden" id="job_id" name="job_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job_number" class="form-label required">Job Number</label>
                            <input type="text" id="job_number" name="job_number" class="form-control" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="order_id" class="form-label required">Order</label>
                            <select id="order_id" name="order_id" class="form-control" required onchange="loadOrderItems()">
                                <option value="">Select Order</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="order_item_id" class="form-label required">Order Item (Product)</label>
                            <select id="order_item_id" name="order_item_id" class="form-control" required>
                                <option value="">Select Order First</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity" class="form-label required">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                            <small class="form-text" id="orderQuantityHint"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="department_id" class="form-label required">Department</label>
                            <select id="department_id" name="department_id" class="form-control" required onchange="loadDepartmentResources()">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="production_stage_id" class="form-label">Production Stage</label>
                            <select id="production_stage_id" name="production_stage_id" class="form-control">
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="machine_id" class="form-label">Machine</label>
                            <select id="machine_id" name="machine_id" class="form-control">
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assigned_to" class="form-label">Assigned To (Operator)</label>
                            <select id="assigned_to" name="assigned_to" class="form-control">
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="scheduled_start" class="form-label required">Scheduled Start Date</label>
                            <input type="date" id="scheduled_start" name="scheduled_start" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="scheduled_end" class="form-label required">Scheduled End Date</label>
                            <input type="date" id="scheduled_end" name="scheduled_end" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="job_notes" class="form-label">Job Notes</label>
                        <textarea id="job_notes" name="job_notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label required">Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="on_hold">On Hold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="priority" class="form-label">Priority</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeJobModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Job</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Job Details Modal -->
    <div id="viewJobModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Job Details - <span id="viewJobNumber"></span></h2>
                <button type="button" class="close-modal" onclick="closeViewJobModal()">&times;</button>
            </div>
            <div class="modal-body" id="jobDetailsContent">
                <p class="text-center">Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewJobModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/schedule.js"></script>
</body>
</html>
