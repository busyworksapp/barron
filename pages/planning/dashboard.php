<?php
/**
 * Barron Production Management System
 * Planning Dashboard
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Planning.php';

// Check permissions
if (!checkPermission('planning.view')) {
    header('Location: /pages/dashboard.php?error=access_denied');
    exit;
}

$planning = new Planning();
$stats = $planning->getDashboardStats();

$page_title = 'Planning Dashboard';
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-clipboard-list"></i> Planning Dashboard</h1>
    <div class="header-actions">
        <?php if (checkPermission('planning.create')): ?>
            <a href="/pages/planning/orders.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Order
            </a>
            <a href="/pages/planning/import.php" class="btn btn-secondary">
                <i class="fas fa-upload"></i> Import Orders
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['total_orders'] ?></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-change">
                <span class="positive">+<?= $stats['pending_orders'] ?></span> pending
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['total_jobs'] ?></div>
            <div class="stat-label">Total Jobs</div>
            <div class="stat-change">
                <span class="warning"><?= $stats['scheduled_jobs'] ?></span> scheduled
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['in_progress_jobs'] ?></div>
            <div class="stat-label">In Progress</div>
            <div class="stat-change">
                <span class="info">Active now</span>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['completed_jobs'] ?></div>
            <div class="stat-label">Completed</div>
            <div class="stat-change">
                <span class="positive">This month</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Alerts -->
<div class="dashboard-row">
    <div class="dashboard-col-8">
        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Orders</h3>
                <a href="/pages/planning/orders.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Jobs</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recentOrdersTable">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="loading-spinner"></div>
                                    Loading orders...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Active Jobs -->
        <div class="card mt-20">
            <div class="card-header">
                <h3>Active Jobs</h3>
                <a href="/pages/planning/schedule.php" class="btn-link">View Schedule <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Job #</th>
                                <th>Order</th>
                                <th>Department</th>
                                <th>Stage</th>
                                <th>Operator</th>
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="activeJobsTable">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="loading-spinner"></div>
                                    Loading jobs...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-col-4">
        <!-- Capacity Overview -->
        <div class="card">
            <div class="card-header">
                <h3>Capacity Overview</h3>
                <a href="/pages/planning/capacity.php" class="btn-link">Details <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="card-body">
                <div id="capacityOverview">
                    <div class="loading-spinner"></div>
                    Loading capacity data...
                </div>
            </div>
        </div>
        
        <!-- Priority Alerts -->
        <div class="card mt-20">
            <div class="card-header">
                <h3>Priority Alerts</h3>
            </div>
            <div class="card-body">
                <div id="priorityAlerts">
                    <div class="loading-spinner"></div>
                    Loading alerts...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load dashboard data
document.addEventListener('DOMContentLoaded', function() {
    loadRecentOrders();
    loadActiveJobs();
    loadCapacityOverview();
    loadPriorityAlerts();
    
    // Refresh every 30 seconds
    setInterval(loadActiveJobs, 30000);
});

function loadRecentOrders() {
    fetch('/api/planning/orders.php?per_page=10&sort=created_at&order=desc')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRecentOrders(data.orders);
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            document.getElementById('recentOrdersTable').innerHTML = 
                '<tr><td colspan="7" class="text-center error">Failed to load orders</td></tr>';
        });
}

function renderRecentOrders(orders) {
    const tbody = document.getElementById('recentOrdersTable');
    
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No orders found</td></tr>';
        return;
    }
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td><a href="/pages/planning/orders.php?id=${order.id}">${order.order_number}</a></td>
            <td>${order.customer_name}</td>
            <td>${formatDate(order.due_date)}</td>
            <td><span class="badge badge-${order.priority}">${order.priority}</span></td>
            <td><span class="badge badge-${order.status}">${order.status}</span></td>
            <td>${order.job_count || 0}</td>
            <td>
                <a href="/pages/planning/orders.php?id=${order.id}" class="btn-icon" title="View">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

function loadActiveJobs() {
    fetch('/api/planning/jobs.php?status=in_progress,scheduled&per_page=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderActiveJobs(data.jobs);
            }
        })
        .catch(error => {
            console.error('Error loading jobs:', error);
        });
}

function renderActiveJobs(jobs) {
    const tbody = document.getElementById('activeJobsTable');
    
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No active jobs</td></tr>';
        return;
    }
    
    tbody.innerHTML = jobs.map(job => {
        const progress = job.quantity_planned > 0 
            ? Math.round((job.quantity_completed / job.quantity_planned) * 100) 
            : 0;
        
        return `
            <tr>
                <td>${job.job_number}</td>
                <td><a href="/pages/planning/orders.php?id=${job.order_id}">${job.order_number}</a></td>
                <td>${job.department_name}</td>
                <td>${job.stage_name || '-'}</td>
                <td>${job.operator_name || 'Unassigned'}</td>
                <td>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: ${progress}%"></div>
                        <span class="progress-text">${progress}%</span>
                    </div>
                </td>
                <td><span class="badge badge-${job.status}">${job.status}</span></td>
            </tr>
        `;
    }).join('');
}

function loadCapacityOverview() {
    const today = new Date().toISOString().split('T')[0];
    const nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    fetch(`/api/planning/capacity.php?action=overview&date_from=${today}&date_to=${nextWeek}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCapacityOverview(data.departments);
            }
        })
        .catch(error => {
            console.error('Error loading capacity:', error);
            document.getElementById('capacityOverview').innerHTML = 
                '<p class="error">Failed to load capacity data</p>';
        });
}

function renderCapacityOverview(departments) {
    const container = document.getElementById('capacityOverview');
    
    if (departments.length === 0) {
        container.innerHTML = '<p class="text-muted">No capacity data available</p>';
        return;
    }
    
    container.innerHTML = departments.map(dept => `
        <div class="capacity-item">
            <div class="capacity-header">
                <strong>${dept.department_name}</strong>
                <span class="badge badge-${dept.status}">${dept.utilization_percent}%</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar progress-${dept.status}" style="width: ${Math.min(dept.utilization_percent, 100)}%"></div>
            </div>
            <div class="capacity-stats">
                <span>${dept.total_jobs} jobs</span>
                <span>${dept.available_capacity} available</span>
            </div>
        </div>
    `).join('');
}

function loadPriorityAlerts() {
    // Load overdue orders and high priority items
    fetch('/api/planning/orders.php?priority=urgent,high&per_page=5')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPriorityAlerts(data.orders);
            }
        })
        .catch(error => {
            console.error('Error loading alerts:', error);
            document.getElementById('priorityAlerts').innerHTML = 
                '<p class="error">Failed to load alerts</p>';
        });
}

function renderPriorityAlerts(orders) {
    const container = document.getElementById('priorityAlerts');
    
    if (orders.length === 0) {
        container.innerHTML = '<p class="text-muted">No priority alerts</p>';
        return;
    }
    
    container.innerHTML = orders.map(order => {
        const daysUntilDue = Math.ceil((new Date(order.due_date) - new Date()) / (1000 * 60 * 60 * 24));
        const isOverdue = daysUntilDue < 0;
        
        return `
            <div class="alert-item ${isOverdue ? 'alert-danger' : 'alert-warning'}">
                <div class="alert-icon">
                    <i class="fas fa-${isOverdue ? 'exclamation-triangle' : 'clock'}"></i>
                </div>
                <div class="alert-content">
                    <strong>${order.order_number}</strong>
                    <p>${order.customer_name}</p>
                    <small>${isOverdue ? 'Overdue by ' + Math.abs(daysUntilDue) + ' days' : 'Due in ' + daysUntilDue + ' days'}</small>
                </div>
                <a href="/pages/planning/orders.php?id=${order.id}" class="alert-action">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        `;
    }).join('');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin: 5px 0;
}

.stat-change {
    font-size: 12px;
}

.stat-change .positive { color: #10b981; }
.stat-change .warning { color: #f59e0b; }
.stat-change .info { color: #3b82f6; }

.dashboard-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.capacity-item {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.capacity-item:last-child {
    border-bottom: none;
}

.capacity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.capacity-stats {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.alert-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 10px;
}

.alert-danger {
    background: #fee;
    border-left: 4px solid #dc2626;
}

.alert-warning {
    background: #fffbeb;
    border-left: 4px solid #f59e0b;
}

.alert-icon {
    font-size: 20px;
    width: 30px;
    text-align: center;
}

.alert-danger .alert-icon { color: #dc2626; }
.alert-warning .alert-icon { color: #f59e0b; }

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    margin-bottom: 3px;
}

.alert-content p {
    margin: 0 0 3px 0;
    font-size: 13px;
}

.alert-content small {
    color: #666;
    font-size: 12px;
}

.alert-action {
    align-self: center;
    color: #666;
    font-size: 16px;
}

.progress-bar-container {
    background: #e5e7eb;
    border-radius: 4px;
    height: 8px;
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    background: #3b82f6;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-bar.progress-overbooked { background: #dc2626; }
.progress-bar.progress-high { background: #f59e0b; }
.progress-bar.progress-normal { background: #10b981; }

.progress-text {
    position: absolute;
    right: 5px;
    top: -18px;
    font-size: 11px;
    color: #666;
}

@media (max-width: 968px) {
    .dashboard-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
