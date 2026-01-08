<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('sop.view')) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'NCR Reports';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="content-header">
        <div class="header-left">
            <h1 class="page-title">Non-Conformance Reports (NCR)</h1>
            <p class="page-subtitle">Document and track non-conformances with CAPA management</p>
        </div>
        <div class="header-right">
            <?php if (hasPermission('sop.edit')): ?>
            <button class="btn btn-primary" onclick="showNCRModal()">
                <span class="btn-icon">➕</span>
                Create NCR
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #ff6b6b;">📋</div>
            <div class="stat-details">
                <div class="stat-value" id="openNCRCount">0</div>
                <div class="stat-label">Open NCRs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #51cf66;">✓</div>
            <div class="stat-details">
                <div class="stat-value" id="closedNCRCount">0</div>
                <div class="stat-label">Closed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ffa94d;">📅</div>
            <div class="stat-details">
                <div class="stat-value" id="thisMonthCount">0</div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ff8787;">⏰</div>
            <div class="stat-details">
                <div class="stat-value" id="overdueCount">0</div>
                <div class="stat-label">Overdue CAPA</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by NCR#, description...">
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="form-control">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="investigation">Investigation</option>
                <option value="capa_pending">CAPA Pending</option>
                <option value="capa_in_progress">CAPA In Progress</option>
                <option value="verification">Verification</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="typeFilter" class="form-control">
                <option value="">All Types</option>
                <option value="internal">Internal</option>
                <option value="supplier">Supplier</option>
                <option value="customer">Customer</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="departmentFilter" class="form-control">
                <option value="">All Departments</option>
            </select>
        </div>
        <div class="filter-group">
            <input type="date" id="dateFromFilter" class="form-control" placeholder="From Date">
        </div>
    </div>

    <!-- NCR Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NCR #</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Date Raised</th>
                    <th>Target Closure</th>
                    <th>Raised By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ncrTableBody">
                <tr>
                    <td colspan="9" class="text-center">Loading NCRs...</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<!-- NCR Modal -->
<div id="ncrModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Create NCR</h2>
            <button class="modal-close" onclick="closeNCRModal()">&times;</button>
        </div>
        <form id="ncrForm">
            <input type="hidden" id="ncr_id" name="ncr_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ncr_number">NCR Number *</label>
                    <input type="text" id="ncr_number" name="ncr_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="ncr_type">NCR Type *</label>
                    <select id="ncr_type" name="ncr_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="internal">Internal - Production/Process</option>
                        <option value="supplier">Supplier - Incoming Material</option>
                        <option value="customer">Customer - Product Complaint</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="department_id">Department *</label>
                    <select id="department_id" name="department_id" class="form-control" required>
                        <option value="">Select Department</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_raised">Date Raised *</label>
                    <input type="date" id="date_raised" name="date_raised" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Non-Conformance Description *</label>
                <textarea id="description" name="description" class="form-control" 
                          rows="3" placeholder="Describe the non-conformance in detail" required></textarea>
            </div>

            <div class="form-group">
                <label for="immediate_action">Immediate Containment Action</label>
                <textarea id="immediate_action" name="immediate_action" class="form-control" 
                          rows="2" placeholder="Actions taken to contain the issue"></textarea>
            </div>

            <div class="form-group">
                <label for="root_cause">Root Cause Analysis</label>
                <textarea id="root_cause" name="root_cause" class="form-control" 
                          rows="3" placeholder="Use 5 Whys, Fishbone, or other RCA method"></textarea>
            </div>

            <div class="form-group">
                <label for="corrective_action">Corrective Action (CA)</label>
                <textarea id="corrective_action" name="corrective_action" class="form-control" 
                          rows="2" placeholder="Actions to eliminate the root cause"></textarea>
            </div>

            <div class="form-group">
                <label for="preventive_action">Preventive Action (PA)</label>
                <textarea id="preventive_action" name="preventive_action" class="form-control" 
                          rows="2" placeholder="Actions to prevent similar issues"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <select id="assigned_to" name="assigned_to" class="form-control">
                        <option value="">Select Employee</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="target_closure_date">Target Closure Date</label>
                    <input type="date" id="target_closure_date" name="target_closure_date" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="open">Open</option>
                        <option value="investigation">Investigation</option>
                        <option value="capa_pending">CAPA Pending</option>
                        <option value="capa_in_progress">CAPA In Progress</option>
                        <option value="verification">Verification</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="verification_notes">Verification Notes</label>
                    <input type="text" id="verification_notes" name="verification_notes" class="form-control" 
                           placeholder="Notes on effectiveness verification">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeNCRModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save NCR</button>
            </div>
        </form>
    </div>
</div>

<!-- View NCR Modal -->
<div id="viewNCRModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>NCR Details</h2>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div id="ncrDetailsContent" class="modal-body">
            <!-- Details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/ncr.js"></script>

<?php require_once '../../includes/footer.php'; ?>
