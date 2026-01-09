<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check authentication
requireLogin();

if (!hasPermission('maintenance.view')) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'Preventive Maintenance Schedule';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="content-header">
        <div class="header-left">
            <h1 class="page-title">Preventive Maintenance Schedule</h1>
            <p class="page-subtitle">Plan and track recurring maintenance activities</p>
        </div>
        <div class="header-right">
            <?php if (hasPermission('maintenance.edit')): ?>
            <button class="btn btn-primary" onclick="showScheduleModal()">
                <span class="btn-icon">➕</span>
                Add Schedule
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #339af0;">📅</div>
            <div class="stat-details">
                <div class="stat-value" id="activeSchedulesCount">0</div>
                <div class="stat-label">Active Schedules</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ff6b6b;">⏰</div>
            <div class="stat-details">
                <div class="stat-value" id="overdueCount">0</div>
                <div class="stat-label">Overdue Tasks</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ffa94d;">📋</div>
            <div class="stat-details">
                <div class="stat-value" id="dueThisWeekCount">0</div>
                <div class="stat-label">Due This Week</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #51cf66;">✓</div>
            <div class="stat-details">
                <div class="stat-value" id="completedThisMonthCount">0</div>
                <div class="stat-label">Completed This Month</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by task, machine...">
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="form-control">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="frequencyFilter" class="form-control">
                <option value="">All Frequencies</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="semi_annual">Semi-Annual</option>
                <option value="annual">Annual</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="machineFilter" class="form-control">
                <option value="">All Machines</option>
            </select>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Task Name</th>
                    <th>Machine</th>
                    <th>Frequency</th>
                    <th>Last Performed</th>
                    <th>Next Due</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="schedulesTableBody">
                <tr>
                    <td colspan="8" class="text-center">Loading schedules...</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<!-- Schedule Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle">Add Preventive Maintenance Schedule</h2>
            <button class="modal-close" onclick="closeScheduleModal()">&times;</button>
        </div>
        <form id="scheduleForm">
            <input type="hidden" id="schedule_id" name="schedule_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="task_name">Task Name *</label>
                    <input type="text" id="task_name" name="task_name" class="form-control" 
                           placeholder="e.g., Monthly Oil Change" required>
                </div>
                <div class="form-group">
                    <label for="machine_id">Machine *</label>
                    <select id="machine_id" name="machine_id" class="form-control" required>
                        <option value="">Select Machine</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="task_description">Task Description *</label>
                <textarea id="task_description" name="task_description" class="form-control" 
                          rows="3" placeholder="Detailed steps for performing this maintenance task" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="frequency">Frequency *</label>
                    <select id="frequency" name="frequency" class="form-control" required>
                        <option value="">Select Frequency</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly (Every 3 months)</option>
                        <option value="semi_annual">Semi-Annual (Every 6 months)</option>
                        <option value="annual">Annual (Yearly)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estimated_duration">Estimated Duration (hours) *</label>
                    <input type="number" id="estimated_duration" name="estimated_duration" class="form-control" 
                           step="0.5" min="0.5" placeholder="e.g., 2.5" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <select id="assigned_to" name="assigned_to" class="form-control">
                        <option value="">Select Technician</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="next_due_date">Next Due Date *</label>
                    <input type="date" id="next_due_date" name="next_due_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label for="checklist_items">Checklist Items (one per line)</label>
                <textarea id="checklist_items" name="checklist_items" class="form-control" 
                          rows="4" placeholder="Item 1&#10;Item 2&#10;Item 3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="last_performed_date">Last Performed Date</label>
                    <input type="date" id="last_performed_date" name="last_performed_date" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" 
                          rows="2" placeholder="Additional information"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeScheduleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- View Schedule Modal -->
<div id="viewScheduleModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Schedule Details</h2>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div id="scheduleDetailsContent" class="modal-body">
            <!-- Details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="markAsPerformed()">Mark as Performed</button>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/maintenance_schedule.js"></script>

<?php require_once '../../includes/footer.php'; ?>
