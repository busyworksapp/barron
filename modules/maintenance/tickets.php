<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check authentication
requireLogin();

if (!hasPermission('maintenance.view')) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'Maintenance Tickets';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="content-header">
        <div class="header-left">
            <h1 class="page-title">Maintenance Tickets</h1>
            <p class="page-subtitle">Track equipment maintenance and repair work orders</p>
        </div>
        <div class="header-right">
            <?php if (hasPermission('maintenance.edit')): ?>
            <button class="btn btn-primary" onclick="showTicketModal()">
                <span class="btn-icon">➕</span>
                Create Ticket
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #ff6b6b;">🔧</div>
            <div class="stat-details">
                <div class="stat-value" id="openTicketsCount">0</div>
                <div class="stat-label">Open Tickets</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ffa94d;">⚙️</div>
            <div class="stat-details">
                <div class="stat-value" id="inProgressCount">0</div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #51cf66;">✓</div>
            <div class="stat-details">
                <div class="stat-value" id="completedCount">0</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ff8787;">⚠️</div>
            <div class="stat-details">
                <div class="stat-value" id="urgentCount">0</div>
                <div class="stat-label">Urgent Priority</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by ticket#, machine, description...">
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="form-control">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="assigned">Assigned</option>
                <option value="in_progress">In Progress</option>
                <option value="on_hold">On Hold</option>
                <option value="completed">Completed</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="priorityFilter" class="form-control">
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="typeFilter" class="form-control">
                <option value="">All Types</option>
                <option value="breakdown">Breakdown</option>
                <option value="preventive">Preventive</option>
                <option value="inspection">Inspection</option>
                <option value="calibration">Calibration</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="machineFilter" class="form-control">
                <option value="">All Machines</option>
            </select>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Machine</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created</th>
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
            <h2 id="modalTitle">Create Maintenance Ticket</h2>
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
                    <label for="machine_id">Machine *</label>
                    <select id="machine_id" name="machine_id" class="form-control" required>
                        <option value="">Select Machine</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="maintenance_type">Maintenance Type *</label>
                    <select id="maintenance_type" name="maintenance_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="breakdown">Breakdown - Emergency repair</option>
                        <option value="preventive">Preventive - Scheduled maintenance</option>
                        <option value="inspection">Inspection - Routine check</option>
                        <option value="calibration">Calibration - Equipment adjustment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" class="form-control" required>
                        <option value="">Select Priority</option>
                        <option value="low">Low - Can wait</option>
                        <option value="normal">Normal - Standard timeline</option>
                        <option value="high">High - Urgent attention</option>
                        <option value="urgent">Urgent - Critical/Production stopped</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="issue_description">Issue Description *</label>
                <textarea id="issue_description" name="issue_description" class="form-control" 
                          rows="3" placeholder="Describe the issue or maintenance required" required></textarea>
            </div>

            <div class="form-group">
                <label for="work_performed">Work Performed</label>
                <textarea id="work_performed" name="work_performed" class="form-control" 
                          rows="3" placeholder="Description of work done (filled by technician)"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <select id="assigned_to" name="assigned_to" class="form-control">
                        <option value="">Select Technician</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="scheduled_date">Scheduled Date</label>
                    <input type="datetime-local" id="scheduled_date" name="scheduled_date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="completed_date">Completed Date</label>
                    <input type="datetime-local" id="completed_date" name="completed_date" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="downtime_hours">Downtime Hours</label>
                    <input type="number" id="downtime_hours" name="downtime_hours" class="form-control" 
                           step="0.5" min="0" placeholder="Machine downtime in hours">
                </div>
                <div class="form-group">
                    <label for="cost">Cost ($)</label>
                    <input type="number" id="cost" name="cost" class="form-control" 
                           step="0.01" min="0" placeholder="Parts and labor cost">
                </div>
            </div>

            <div class="form-group">
                <label for="parts_used">Parts Used</label>
                <textarea id="parts_used" name="parts_used" class="form-control" 
                          rows="2" placeholder="List of parts/materials used"></textarea>
            </div>

            <div class="form-group">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" class="form-control" 
                          rows="2" placeholder="Any additional information"></textarea>
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

<script src="<?php echo BASE_URL; ?>assets/js/maintenance_tickets.js"></script>

<?php require_once '../../includes/footer.php'; ?>
