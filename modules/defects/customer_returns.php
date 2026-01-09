<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('defects.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Customer Returns';
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
                        <button type="button" class="btn btn-primary" onclick="openReturnModal()">
                            <span class="icon">+</span> Log Return
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-value" id="openReturnsCount">0</div>
                    <div class="stat-label">Open Returns</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="resolvedReturnsCount">0</div>
                    <div class="stat-label">Resolved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="thisMonthReturnsCount">0</div>
                    <div class="stat-label">This Month</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="returnRatePercent">0%</div>
                    <div class="stat-label">Return Rate</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search returns..." style="max-width: 300px;">
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="received">Received</option>
                            <option value="investigating">Investigating</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="resolved">Resolved</option>
                        </select>
                        <select id="resolutionFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Resolutions</option>
                            <option value="refund">Refund</option>
                            <option value="replacement">Replacement</option>
                            <option value="credit">Credit</option>
                            <option value="repair">Repair</option>
                            <option value="no_action">No Action</option>
                        </select>
                        <input type="date" id="dateFromFilter" class="form-control" style="max-width: 180px;" placeholder="From Date">
                        <button type="button" class="btn btn-secondary" onclick="loadReturns()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>RMA #</th>
                                    <th>Customer</th>
                                    <th>Order</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Resolution</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="returnsTableBody">
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

    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h2 id="modalTitle">Log Customer Return</h2>
                <button type="button" class="close-modal" onclick="closeReturnModal()">&times;</button>
            </div>
            <form id="returnForm">
                <div class="modal-body">
                    <input type="hidden" id="return_id" name="return_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rma_number" class="form-label required">RMA Number</label>
                            <input type="text" id="rma_number" name="rma_number" class="form-control" required maxlength="50">
                            <small class="form-text">Return Merchandise Authorization number</small>
                        </div>
                        <div class="form-group">
                            <label for="order_id" class="form-label required">Order</label>
                            <select id="order_id" name="order_id" class="form-control" required onchange="loadOrderProducts()">
                                <option value="">Select Order</option>
                            </select>
                        </div>
                    </div>

                    <div id="orderInfoSection" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                        <div class="form-row">
                            <div class="form-group">
                                <strong>Customer:</strong> <span id="orderCustomer"></span>
                            </div>
                            <div class="form-group">
                                <strong>Order Date:</strong> <span id="orderDate"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_id" class="form-label required">Product</label>
                            <select id="product_id" name="product_id" class="form-control" required>
                                <option value="">Select Order First</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity_returned" class="form-label required">Quantity Returned</label>
                            <input type="number" id="quantity_returned" name="quantity_returned" class="form-control" min="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="return_reason" class="form-label required">Return Reason</label>
                            <select id="return_reason" name="return_reason" class="form-control" required>
                                <option value="">Select Reason</option>
                                <option value="defective">Defective Product</option>
                                <option value="wrong_item">Wrong Item Shipped</option>
                                <option value="damaged_shipping">Damaged in Shipping</option>
                                <option value="not_as_described">Not as Described</option>
                                <option value="quality_issue">Quality Issue</option>
                                <option value="customer_error">Customer Ordering Error</option>
                                <option value="late_delivery">Late Delivery</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="return_date" class="form-label required">Return Date</label>
                            <input type="date" id="return_date" name="return_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="customer_complaint" class="form-label required">Customer Complaint</label>
                        <textarea id="customer_complaint" name="customer_complaint" class="form-control" rows="3" required placeholder="Detail the customer's complaint..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="investigation_notes" class="form-label">Investigation Notes</label>
                        <textarea id="investigation_notes" name="investigation_notes" class="form-control" rows="2" placeholder="Internal investigation findings..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label required">Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="received">Received</option>
                                <option value="investigating">Investigating</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="resolution_type" class="form-label">Resolution Type</label>
                            <select id="resolution_type" name="resolution_type" class="form-control">
                                <option value="">Pending Decision</option>
                                <option value="refund">Refund</option>
                                <option value="replacement">Replacement</option>
                                <option value="credit">Store Credit</option>
                                <option value="repair">Repair</option>
                                <option value="no_action">No Action</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea id="resolution_notes" name="resolution_notes" class="form-control" rows="2" placeholder="Details about the resolution..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="refund_amount" class="form-label">Refund Amount</label>
                            <input type="number" id="refund_amount" name="refund_amount" class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="restocking_fee" class="form-label">Restocking Fee</label>
                            <input type="number" id="restocking_fee" name="restocking_fee" class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeReturnModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Return</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>Return Details - <span id="viewRmaNumber"></span></h2>
                <button type="button" class="close-modal" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="returnDetailsContent">
                <p class="text-center">Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/customer_returns.js"></script>
</body>
</html>
