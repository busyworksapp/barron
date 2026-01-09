<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('production.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Production Tracking';
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
                    <?php if (hasPermission('production.edit')): ?>
                        <button type="button" class="btn btn-success" onclick="openProgressModal()">
                            <span class="icon">+</span> Log Progress
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-value" id="activeJobsCount">0</div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="overdueJobsCount">0</div>
                    <div class="stat-label">Overdue Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="completedTodayCount">0</div>
                    <div class="stat-label">Completed Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="avgCompletionRate">0%</div>
                    <div class="stat-label">Avg Completion</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search jobs..." style="max-width: 300px;">
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress" selected>In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                        <select id="departmentFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Departments</option>
                        </select>
                        <select id="assignedFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Assignments</option>
                            <option value="me">Assigned to Me</option>
                            <option value="unassigned">Unassigned</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="loadJobs()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Job #</th>
                                    <th>Product</th>
                                    <th>Department</th>
                                    <th>Assigned To</th>
                                    <th>Quantity</th>
                                    <th>Progress</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="jobsTableBody">
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

    <!-- Log Progress Modal -->
    <div id="progressModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Log Production Progress</h2>
                <button type="button" class="close-modal" onclick="closeProgressModal()">&times;</button>
            </div>
            <form id="progressForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="job_id" class="form-label required">Select Job</label>
                        <select id="job_id" name="job_id" class="form-control" required onchange="loadJobDetails()">
                            <option value="">Select Job</option>
                        </select>
                    </div>

                    <div id="jobDetailsSection" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                        <h3 style="margin-top: 0; font-size: 1.1rem;">Job Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <strong>Product:</strong> <span id="jobProduct"></span>
                            </div>
                            <div class="form-group">
                                <strong>Total Quantity:</strong> <span id="jobQuantity"></span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <strong>Produced So Far:</strong> <span id="jobProduced"></span>
                            </div>
                            <div class="form-group">
                                <strong>Remaining:</strong> <span id="jobRemaining"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <strong>Current Progress:</strong> <span id="jobProgress"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity_produced" class="form-label required">Quantity Produced</label>
                            <input type="number" id="quantity_produced" name="quantity_produced" class="form-control" min="1" required>
                            <small class="form-text">Enter the quantity produced in this session</small>
                        </div>
                        <div class="form-group">
                            <label for="quantity_rejected" class="form-label">Quantity Rejected</label>
                            <input type="number" id="quantity_rejected" name="quantity_rejected" class="form-control" min="0" value="0">
                            <small class="form-text">Enter defective/rejected units</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="production_notes" class="form-label">Production Notes</label>
                        <textarea id="production_notes" name="production_notes" class="form-control" rows="3" placeholder="Any issues, observations, or comments..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="log_time" class="form-label required">Log Time</label>
                            <input type="datetime-local" id="log_time" name="log_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="mark_started" name="mark_started"> Mark Job as Started
                            </label>
                            <small class="form-text" style="display: block;">Check if this is the first production entry</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeProgressModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Progress</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Job Status</h2>
                <button type="button" class="close-modal" onclick="closeStatusModal()">&times;</button>
            </div>
            <form id="statusForm">
                <div class="modal-body">
                    <input type="hidden" id="status_job_id" name="job_id">
                    
                    <div class="form-group">
                        <label class="form-label">Job:</label>
                        <div id="statusJobInfo" style="padding: 0.5rem; background: #f8f9fa; border-radius: 4px;"></div>
                    </div>

                    <div class="form-group">
                        <label for="new_status" class="form-label required">New Status</label>
                        <select id="new_status" name="status" class="form-control" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status_notes" class="form-label">Reason/Notes</label>
                        <textarea id="status_notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Production Logs Modal -->
    <div id="logsModal" class="modal">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h2>Production Logs - <span id="logsJobNumber"></span></h2>
                <button type="button" class="close-modal" onclick="closeLogsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="logsContent">
                    <p class="text-center">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLogsModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/tracking.js"></script>
</body>
</html>
