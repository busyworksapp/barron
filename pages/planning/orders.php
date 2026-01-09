<?php
/**
 * Barron Production Management System
 * Orders Management
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
$database = new Database();
$conn = $database->getConnection();

// Handle single order view
$view_order = null;
if (isset($_GET['id'])) {
    $view_order = $planning->getOrderDetails($_GET['id']);
}

$page_title = $view_order ? 'Order ' . $view_order['order_number'] : 'Orders Management';
include_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($view_order): ?>
    <!-- Single Order View -->
    <div class="page-header">
        <div>
            <a href="/pages/planning/orders.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <h1>Order <?= htmlspecialchars($view_order['order_number']) ?></h1>
            <p class="text-muted">Created <?= date('M d, Y', strtotime($view_order['created_at'])) ?></p>
        </div>
        <div class="header-actions">
            <?php if (checkPermission('planning.edit')): ?>
                <button class="btn btn-secondary" onclick="editOrder(<?= $view_order['id'] ?>)">
                    <i class="fas fa-edit"></i> Edit Order
                </button>
            <?php endif; ?>
            <?php if (checkPermission('planning.create')): ?>
                <button class="btn btn-primary" onclick="createJob(<?= $view_order['id'] ?>)">
                    <i class="fas fa-plus"></i> Create Job
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Card -->
    <div class="detail-grid">
        <div class="detail-col-8">
            <!-- Order Information -->
            <div class="card">
                <div class="card-header">
                    <h3>Order Information</h3>
                    <span class="badge badge-<?= $view_order['status'] ?>"><?= $view_order['status'] ?></span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Order Number</label>
                            <value><?= htmlspecialchars($view_order['order_number']) ?></value>
                        </div>
                        <div class="info-item">
                            <label>Customer</label>
                            <value><?= htmlspecialchars($view_order['customer_name']) ?></value>
                        </div>
                        <div class="info-item">
                            <label>Order Date</label>
                            <value><?= date('M d, Y', strtotime($view_order['order_date'])) ?></value>
                        </div>
                        <div class="info-item">
                            <label>Due Date</label>
                            <value><?= date('M d, Y', strtotime($view_order['due_date'])) ?></value>
                        </div>
                        <div class="info-item">
                            <label>Priority</label>
                            <value><span class="badge badge-<?= $view_order['priority'] ?>"><?= $view_order['priority'] ?></span></value>
                        </div>
                        <div class="info-item">
                            <label>Created By</label>
                            <value><?= htmlspecialchars($view_order['created_by_name'] ?? 'System') ?></value>
                        </div>
                        <?php if ($view_order['customer_email']): ?>
                        <div class="info-item">
                            <label>Email</label>
                            <value><?= htmlspecialchars($view_order['customer_email']) ?></value>
                        </div>
                        <?php endif; ?>
                        <?php if ($view_order['customer_phone']): ?>
                        <div class="info-item">
                            <label>Phone</label>
                            <value><?= htmlspecialchars($view_order['customer_phone']) ?></value>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($view_order['notes']): ?>
                        <div class="info-item mt-20">
                            <label>Notes</label>
                            <value><?= nl2br(htmlspecialchars($view_order['notes'])) ?></value>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3>Order Items</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($view_order['items'])): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($view_order['items'] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                            <td>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                                            <td><?= htmlspecialchars($item['notes'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No items in this order</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Jobs -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3>Production Jobs</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($view_order['jobs'])): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Job #</th>
                                        <th>Department</th>
                                        <th>Stage</th>
                                        <th>Operator</th>
                                        <th>Quantity</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($view_order['jobs'] as $job): ?>
                                        <?php 
                                            $progress = $job['quantity_planned'] > 0 
                                                ? round(($job['quantity_completed'] / $job['quantity_planned']) * 100) 
                                                : 0;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($job['job_number']) ?></td>
                                            <td><?= htmlspecialchars($job['department_name']) ?></td>
                                            <td><?= htmlspecialchars($job['stage_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($job['operator_name'] ?? 'Unassigned') ?></td>
                                            <td><?= $job['quantity_completed'] ?> / <?= $job['quantity_planned'] ?></td>
                                            <td>
                                                <div class="progress-bar-inline">
                                                    <div class="progress" style="width: <?= $progress ?>%"></div>
                                                    <span><?= $progress ?>%</span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-<?= $job['status'] ?>"><?= $job['status'] ?></span></td>
                                            <td>
                                                <?php if (checkPermission('planning.edit')): ?>
                                                    <button class="btn-icon" onclick="editJob(<?= $job['id'] ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No jobs created for this order yet</p>
                        <?php if (checkPermission('planning.create')): ?>
                            <button class="btn btn-primary mt-10" onclick="createJob(<?= $view_order['id'] ?>)">
                                <i class="fas fa-plus"></i> Create First Job
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="detail-col-4">
            <!-- Timeline/Activity -->
            <div class="card">
                <div class="card-header">
                    <h3>Activity Timeline</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Order Created</strong>
                                <p><?= date('M d, Y g:i A', strtotime($view_order['created_at'])) ?></p>
                                <small>By <?= htmlspecialchars($view_order['created_by_name'] ?? 'System') ?></small>
                            </div>
                        </div>
                        <?php if (!empty($view_order['jobs'])): ?>
                            <?php foreach (array_slice($view_order['jobs'], 0, 5) as $job): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <strong>Job Created</strong>
                                        <p><?= htmlspecialchars($job['job_number']) ?></p>
                                        <small><?= date('M d, Y', strtotime($job['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Orders List View -->
    <div class="page-header">
        <h1><i class="fas fa-file-alt"></i> Orders Management</h1>
        <div class="header-actions">
            <?php if (checkPermission('planning.create')): ?>
                <button class="btn btn-secondary" onclick="showImportModal()">
                    <i class="fas fa-upload"></i> Import
                </button>
                <button class="btn btn-primary" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i> New Order
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-body">
            <div class="filters-grid">
                <div class="filter-item">
                    <label>Search</label>
                    <input type="text" id="searchInput" placeholder="Order #, Customer...">
                </div>
                <div class="filter-item">
                    <label>Status</label>
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Priority</label>
                    <select id="priorityFilter">
                        <option value="">All Priorities</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Date From</label>
                    <input type="date" id="dateFromFilter">
                </div>
                <div class="filter-item">
                    <label>Date To</label>
                    <input type="date" id="dateToFilter">
                </div>
                <div class="filter-item filter-actions">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card mt-20">
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Jobs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="loading-spinner"></div>
                                Loading orders...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="paginationContainer" class="pagination-container"></div>
        </div>
    </div>
<?php endif; ?>

<!-- Create/Edit Order Modal -->
<div id="orderModal" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Create New Order</h3>
                <button class="btn-close" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="orderForm">
                    <input type="hidden" id="orderId" name="id">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Order Number <span class="required">*</span></label>
                            <input type="text" id="orderNumber" name="order_number" required>
                        </div>
                        <div class="form-group">
                            <label>Customer Name <span class="required">*</span></label>
                            <input type="text" id="customerName" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label>Customer Email</label>
                            <input type="email" id="customerEmail" name="customer_email">
                        </div>
                        <div class="form-group">
                            <label>Customer Phone</label>
                            <input type="tel" id="customerPhone" name="customer_phone">
                        </div>
                        <div class="form-group">
                            <label>Order Date <span class="required">*</span></label>
                            <input type="date" id="orderDate" name="order_date" required>
                        </div>
                        <div class="form-group">
                            <label>Due Date <span class="required">*</span></label>
                            <input type="date" id="dueDate" name="due_date" required>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select id="priority" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="status" name="status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('orderModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveOrder()">
                    <i class="fas fa-save"></i> Save Order
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
    <?php if (!$view_order): ?>
        loadOrders();
        
        // Set default date to today
        document.getElementById('orderDate').valueAsDate = new Date();
    <?php endif; ?>
});

function loadOrders(page = 1) {
    currentPage = page;
    
    const params = new URLSearchParams({
        page: page,
        per_page: 20,
        ...currentFilters
    });
    
    fetch(`/api/planning/orders.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderOrders(data.orders);
                renderPagination(data.pagination);
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            showAlert('Failed to load orders', 'error');
        });
}

function renderOrders(orders) {
    const tbody = document.getElementById('ordersTableBody');
    
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No orders found</td></tr>';
        return;
    }
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td><a href="/pages/planning/orders.php?id=${order.id}">${order.order_number}</a></td>
            <td>${escapeHtml(order.customer_name)}</td>
            <td>${formatDate(order.order_date)}</td>
            <td>${formatDate(order.due_date)}</td>
            <td><span class="badge badge-${order.priority}">${order.priority}</span></td>
            <td><span class="badge badge-${order.status}">${order.status}</span></td>
            <td>${order.job_count || 0}</td>
            <td class="actions-cell">
                <a href="/pages/planning/orders.php?id=${order.id}" class="btn-icon" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                ${canEdit() ? `
                    <button class="btn-icon" onclick="editOrder(${order.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination">';
    
    if (pagination.current_page > 1) {
        html += `<button onclick="loadOrders(${pagination.current_page - 1})">&laquo; Previous</button>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            html += `<button class="active">${i}</button>`;
        } else {
            html += `<button onclick="loadOrders(${i})">${i}</button>`;
        }
    }
    
    if (pagination.current_page < pagination.total_pages) {
        html += `<button onclick="loadOrders(${pagination.current_page + 1})">Next &raquo;</button>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

function applyFilters() {
    currentFilters = {
        search: document.getElementById('searchInput').value,
        status: document.getElementById('statusFilter').value,
        priority: document.getElementById('priorityFilter').value,
        date_from: document.getElementById('dateFromFilter').value,
        date_to: document.getElementById('dateToFilter').value
    };
    
    loadOrders(1);
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';
    
    currentFilters = {};
    loadOrders(1);
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Order';
    document.getElementById('orderForm').reset();
    document.getElementById('orderId').value = '';
    document.getElementById('orderDate').valueAsDate = new Date();
    openModal('orderModal');
}

function editOrder(orderId) {
    fetch(`/api/planning/orders.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Order';
                const order = data.order;
                document.getElementById('orderId').value = order.id;
                document.getElementById('orderNumber').value = order.order_number;
                document.getElementById('customerName').value = order.customer_name;
                document.getElementById('customerEmail').value = order.customer_email || '';
                document.getElementById('customerPhone').value = order.customer_phone || '';
                document.getElementById('orderDate').value = order.order_date;
                document.getElementById('dueDate').value = order.due_date;
                document.getElementById('priority').value = order.priority;
                document.getElementById('status').value = order.status;
                document.getElementById('notes').value = order.notes || '';
                openModal('orderModal');
            }
        })
        .catch(error => {
            console.error('Error loading order:', error);
            showAlert('Failed to load order details', 'error');
        });
}

function saveOrder() {
    const orderId = document.getElementById('orderId').value;
    const formData = {
        order_number: document.getElementById('orderNumber').value,
        customer_name: document.getElementById('customerName').value,
        customer_email: document.getElementById('customerEmail').value,
        customer_phone: document.getElementById('customerPhone').value,
        order_date: document.getElementById('orderDate').value,
        due_date: document.getElementById('dueDate').value,
        priority: document.getElementById('priority').value,
        status: document.getElementById('status').value,
        notes: document.getElementById('notes').value,
        items: [] // Items would be added in a more complete implementation
    };
    
    if (orderId) {
        formData.id = orderId;
    }
    
    const url = '/api/planning/orders.php';
    const method = orderId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('orderModal');
            if (orderId) {
                location.reload();
            } else {
                loadOrders(currentPage);
            }
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving order:', error);
        showAlert('Failed to save order', 'error');
    });
}

function canEdit() {
    return <?= checkPermission('planning.edit') ? 'true' : 'false' ?>;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    text-transform: uppercase;
    font-weight: 600;
}

.info-item value {
    display: block;
    font-size: 15px;
    color: #333;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-icon {
    position: absolute;
    left: -25px;
    width: 20px;
    height: 20px;
    background: white;
    border: 2px solid #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #3b82f6;
}

.timeline-content strong {
    display: block;
    margin-bottom: 5px;
}

.timeline-content p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.timeline-content small {
    color: #999;
    font-size: 12px;
}

.progress-bar-inline {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 150px;
}

.progress-bar-inline .progress {
    flex: 1;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    position: relative;
}

.progress-bar-inline .progress::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background: #3b82f6;
    border-radius: 3px;
    width: var(--progress, 0%);
}

.progress-bar-inline span {
    font-size: 12px;
    color: #666;
    white-space: nowrap;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 968px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
