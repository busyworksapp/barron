<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('defects.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Internal Rejects';
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
                    <?php if (hasPermission('defects.edit')): ?>
                        <button type="button" class="btn btn-primary" onclick="openRejectModal()">
                            <span class="icon">+</span> Log Reject
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-value" id="pendingRejectsCount">0</div>
                    <div class="stat-label">Pending Approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="approvedRejectsCount">0</div>
                    <div class="stat-label">Approved Rejects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="thisMonthRejectsCount">0</div>
                    <div class="stat-label">This Month</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="rejectRatePercent">0%</div>
                    <div class="stat-label">Reject Rate</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search rejects..." style="max-width: 300px;">
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select id="departmentFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Departments</option>
                        </select>
                        <input type="date" id="dateFromFilter" class="form-control" style="max-width: 180px;" placeholder="From Date">
                        <button type="button" class="btn btn-secondary" onclick="loadRejects()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Reject #</th>
                                    <th>Job</th>
                                    <th>Product</th>
                                    <th>Department</th>
                                    <th>Quantity</th>
                                    <th>Defect Type</th>
                                    <th>Reported By</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rejectsTableBody">
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

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="modalTitle">Log Internal Reject</h2>
                <button type="button" class="close-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" id="reject_id" name="reject_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reject_number" class="form-label required">Reject Number</label>
                            <input type="text" id="reject_number" name="reject_number" class="form-control" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="job_id" class="form-label required">Job</label>
                            <select id="job_id" name="job_id" class="form-control" required onchange="loadJobInfo()">
                                <option value="">Select Job</option>
                            </select>
                        </div>
                    </div>

                    <div id="jobInfoSection" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                        <div class="form-row">
                            <div class="form-group">
                                <strong>Product:</strong> <span id="jobProduct"></span>
                            </div>
                            <div class="form-group">
                                <strong>Order:</strong> <span id="jobOrder"></span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <strong>Department:</strong> <span id="jobDepartment"></span>
                            </div>
                            <div class="form-group">
                                <strong>Quantity Produced:</strong> <span id="jobProduced"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity_rejected" class="form-label required">Quantity Rejected</label>
                            <input type="number" id="quantity_rejected" name="quantity_rejected" class="form-control" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="defect_type" class="form-label required">Defect Type</label>
                            <select id="defect_type" name="defect_type" class="form-control" required>
                                <option value="">Select Defect Type</option>
                                <option value="material_defect">Material Defect</option>
                                <option value="workmanship">Workmanship</option>
                                <option value="machine_error">Machine Error</option>
                                <option value="measurement_error">Measurement Error</option>
                                <option value="color_mismatch">Color Mismatch</option>
                                <option value="contamination">Contamination</option>
                                <option value="incomplete">Incomplete</option>
                                <option value="damaged">Damaged</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="defect_description" class="form-label required">Defect Description</label>
                        <textarea id="defect_description" name="defect_description" class="form-control" rows="3" required placeholder="Describe the defect in detail..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="root_cause" class="form-label">Root Cause (if known)</label>
                        <textarea id="root_cause" name="root_cause" class="form-control" rows="2" placeholder="What caused this defect?"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="reject_date" class="form-label required">Reject Date</label>
                            <input type="date" id="reject_date" name="reject_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="disposition" class="form-label">Disposition</label>
                            <select id="disposition" name="disposition" class="form-control">
                                <option value="scrap">Scrap</option>
                                <option value="rework">Rework</option>
                                <option value="use_as_is">Use As-Is</option>
                                <option value="return_supplier">Return to Supplier</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Reject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Approve/Reject</h2>
                <button type="button" class="close-modal" onclick="closeApprovalModal()">&times;</button>
            </div>
            <form id="approvalForm">
                <div class="modal-body">
                    <input type="hidden" id="approval_reject_id">
                    
                    <div class="form-group">
                        <label class="form-label">Reject Details:</label>
                        <div id="approvalRejectInfo" style="padding: 0.5rem; background: #f8f9fa; border-radius: 4px;"></div>
                    </div>

                    <div class="form-group">
                        <label for="approval_decision" class="form-label required">Decision</label>
                        <select id="approval_decision" name="decision" class="form-control" required>
                            <option value="">Select Decision</option>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="approval_notes" class="form-label required">Notes</label>
                        <textarea id="approval_notes" name="notes" class="form-control" rows="3" required placeholder="Enter approval notes or reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeApprovalModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Decision</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Reject Details - <span id="viewRejectNumber"></span></h2>
                <button type="button" class="close-modal" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="rejectDetailsContent">
                <p class="text-center">Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/internal_rejects.js"></script>
</body>
</html>
