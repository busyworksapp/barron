<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if (!hasPermission('planning.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Orders Management';
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
                        <button type="button" class="btn btn-primary" onclick="openOrderModal()">
                            <span class="icon">+</span> New Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search orders..." style="max-width: 300px;">
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="date" id="dateFromFilter" class="form-control" style="max-width: 180px;" placeholder="From Date">
                        <input type="date" id="dateToFilter" class="form-control" style="max-width: 180px;" placeholder="To Date">
                        <button type="button" class="btn btn-secondary" onclick="loadOrders()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Order Date</th>
                                    <th>Due Date</th>
                                    <th>Items</th>
                                    <th>Total Qty</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
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

    <!-- Order Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h2 id="modalTitle">New Order</h2>
                <button type="button" class="close-modal" onclick="closeOrderModal()">&times;</button>
            </div>
            <form id="orderForm">
                <div class="modal-body">
                    <input type="hidden" id="order_id" name="order_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="order_number" class="form-label required">Order Number</label>
                            <input type="text" id="order_number" name="order_number" class="form-control" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="customer_name" class="form-label required">Customer Name</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" required maxlength="200">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_ref" class="form-label">Customer Reference</label>
                            <input type="text" id="customer_ref" name="customer_ref" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="po_number" class="form-label">PO Number</label>
                            <input type="text" id="po_number" name="po_number" class="form-control" maxlength="100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="order_date" class="form-label required">Order Date</label>
                            <input type="date" id="order_date" name="order_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="due_date" class="form-label required">Due Date</label>
                            <input type="date" id="due_date" name="due_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">Order Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <hr>
                    <h3>Order Items</h3>
                    <div id="orderItemsContainer">
                        <div class="order-item-row" data-index="0">
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <label class="form-label required">Product</label>
                                    <select class="form-control item-product" required>
                                        <option value="">Select Product</option>
                                    </select>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label required">Quantity</label>
                                    <input type="number" class="form-control item-quantity" min="1" required>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Unit Price</label>
                                    <input type="number" class="form-control item-price" step="0.01" min="0">
                                </div>
                                <div class="form-group" style="flex: 0 0 auto; padding-top: 28px;">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(0)" title="Remove">
                                        <span class="icon">✖</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Item Notes</label>
                                    <input type="text" class="form-control item-notes" maxlength="500">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addOrderItem()">
                        <span class="icon">+</span> Add Item
                    </button>

                    <hr>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label required">Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
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
                    <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Order Details Modal -->
    <div id="viewOrderModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Order Details - <span id="viewOrderNumber"></span></h2>
                <button type="button" class="close-modal" onclick="closeViewOrderModal()">&times;</button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <p class="text-center">Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewOrderModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/orders.js"></script>
</body>
</html>
