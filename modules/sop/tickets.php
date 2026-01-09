<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('sop.view')) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'SOP Failure Tickets';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="content-header">
        <div class="header-left">
            <h1 class="page-title">SOP Failure Tickets</h1>
            <p class="page-subtitle">Track and manage Standard Operating Procedure failures</p>
        </div>
        <div class="header-right">
            <?php if (hasPermission('sop.edit')): ?>
            <button class="btn btn-primary" onclick="showTicketModal()">
                <span class="btn-icon">➕</span>
                Log SOP Failure
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #ff6b6b;">⚠️</div>
            <div class="stat-details">
                <div class="stat-value" id="openTicketsCount">0</div>
                <div class="stat-label">Open Tickets</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #51cf66;">✓</div>
            <div class="stat-details">
                <div class="stat-value" id="resolvedTicketsCount">0</div>
                <div class="stat-label">Resolved</div>
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
            <div class="stat-icon" style="background: #ff8787;">🔥</div>
            <div class="stat-details">
                <div class="stat-value" id="criticalCount">0</div>
                <div class="stat-label">Critical Severity</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by ticket#, SOP, description...">
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="form-control">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="investigating">Investigating</option>
                <option value="action_required">Action Required</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="severityFilter" class="form-control">
                <option value="">All Severities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
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

    <!-- Tickets Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>SOP Reference</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Reported Date</th>
                    <th>Reported By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ticketsTableBody">
                <tr>
                    <td colspan="9" class="text-center">Loading tickets...</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<!-- Ticket Modal -->
<div id="ticketModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Log SOP Failure</h2>
            <button class="modal-close" onclick="closeTicketModal()">&times;</button>
        </div>
        <form id="ticketForm">
            <input type="hidden" id="ticket_id" name="ticket_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ticket_number">Ticket Number *</label>
                    <input type="text" id="ticket_number" name="ticket_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="sop_reference">SOP Reference *</label>
                    <input type="text" id="sop_reference" name="sop_reference" class="form-control" 
                           placeholder="e.g., SOP-PRD-001" required>
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
                    <label for="severity">Severity *</label>
                    <select id="severity" name="severity" class="form-control" required>
                        <option value="">Select Severity</option>
                        <option value="low">Low - Minor deviation</option>
                        <option value="medium">Medium - Moderate impact</option>
                        <option value="high">High - Significant impact</option>
                        <option value="critical">Critical - Safety/Quality risk</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="failure_description">Failure Description *</label>
                <textarea id="failure_description" name="failure_description" class="form-control" 
                          rows="3" placeholder="Describe what went wrong and how the SOP was not followed" required></textarea>
            </div>

            <div class="form-group">
                <label for="immediate_action">Immediate Action Taken</label>
                <textarea id="immediate_action" name="immediate_action" class="form-control" 
                          rows="2" placeholder="What was done immediately to address the situation"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="incident_date">Incident Date *</label>
                    <input type="date" id="incident_date" name="incident_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="open">Open</option>
                        <option value="investigating">Investigating</option>
                        <option value="action_required">Action Required</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="root_cause">Root Cause Analysis</label>
                <textarea id="root_cause" name="root_cause" class="form-control" 
                          rows="2" placeholder="Identified root cause of the failure"></textarea>
            </div>

            <div class="form-group">
                <label for="corrective_action">Corrective Action Plan</label>
                <textarea id="corrective_action" name="corrective_action" class="form-control" 
                          rows="2" placeholder="Steps to prevent recurrence"></textarea>
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

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTicketModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- View Ticket Modal -->
<div id="viewTicketModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Ticket Details</h2>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div id="ticketDetailsContent" class="modal-body">
            <!-- Details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/sop_tickets.js"></script>

<?php require_once '../../includes/footer.php'; ?>
