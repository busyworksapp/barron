// Customer Returns JavaScript
let orders = [];
let orderProducts = [];
let currentReturnId = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    loadReturns();
    loadSummaryStats();
    
    // Setup form submission
    document.getElementById('returnForm').addEventListener('submit', saveReturn);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadReturns();
        }
    });
    
    // Set default return date to today
    const today = new Date();
    const dateStr = today.toISOString().split('T')[0];
    document.getElementById('return_date').value = dateStr;
});

// Load completed orders for returns
function loadOrders() {
    fetch('../../api/planning/orders/list.php?status=completed')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                orders = data.data;
                const select = document.getElementById('order_id');
                select.innerHTML = '<option value="">Select Order</option>';
                
                orders.forEach(order => {
                    select.innerHTML += `<option value="${order.id}">${escapeHtml(order.order_number)} - ${escapeHtml(order.customer_name)}</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading orders:', error));
}

// Load order products when order is selected
function loadOrderProducts() {
    const orderId = document.getElementById('order_id').value;
    const productSelect = document.getElementById('product_id');
    
    if (!orderId) {
        productSelect.innerHTML = '<option value="">Select Order First</option>';
        document.getElementById('orderInfoSection').style.display = 'none';
        return;
    }
    
    productSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`../../api/planning/orders/get.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.data;
                orderProducts = order.items;
                
                // Display order info
                document.getElementById('orderCustomer').textContent = order.customer_name;
                document.getElementById('orderDate').textContent = formatDate(order.order_date);
                document.getElementById('orderInfoSection').style.display = 'block';
                
                // Populate products
                productSelect.innerHTML = '<option value="">Select Product</option>';
                orderProducts.forEach(item => {
                    productSelect.innerHTML += `<option value="${item.product_id}" data-quantity="${item.quantity}">${escapeHtml(item.product_code)} - ${escapeHtml(item.product_name)} (Qty: ${item.quantity})</option>`;
                });
            } else {
                productSelect.innerHTML = '<option value="">Error loading products</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            productSelect.innerHTML = '<option value="">Error loading products</option>';
        });
}

// Load summary statistics
function loadSummaryStats() {
    fetch('../../api/defects/returns/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('openReturnsCount').textContent = stats.open_count || 0;
                document.getElementById('resolvedReturnsCount').textContent = stats.resolved_count || 0;
                document.getElementById('thisMonthReturnsCount').textContent = stats.this_month_count || 0;
                document.getElementById('returnRatePercent').textContent = stats.return_rate + '%';
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Load returns with filters
function loadReturns() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const resolution = document.getElementById('resolutionFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    
    let url = '../../api/defects/returns/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (status) url += `status=${status}&`;
    if (resolution) url += `resolution_type=${resolution}&`;
    if (dateFrom) url += `date_from=${dateFrom}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReturns(data.data);
            } else {
                showAlert('Error loading returns: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading returns', 'error');
        });
}

// Display returns in table
function displayReturns(returns) {
    const tbody = document.getElementById('returnsTableBody');
    
    if (returns.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No returns found</td></tr>';
        return;
    }
    
    let html = '';
    returns.forEach(ret => {
        const statusBadge = getStatusBadge(ret.status);
        const reasonBadge = getReasonBadge(ret.return_reason);
        const resolutionBadge = ret.resolution_type ? getResolutionBadge(ret.resolution_type) : '-';
        
        html += `
            <tr>
                <td>${escapeHtml(ret.rma_number)}</td>
                <td>${escapeHtml(ret.customer_name)}</td>
                <td>${escapeHtml(ret.order_number)}</td>
                <td>${escapeHtml(ret.product_code)}<br><small>${escapeHtml(ret.product_name)}</small></td>
                <td><span class="badge badge-warning">${ret.quantity_returned}</span></td>
                <td>${reasonBadge}</td>
                <td>${formatDate(ret.return_date)}</td>
                <td>${statusBadge}</td>
                <td>${resolutionBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-secondary" onclick="viewReturn(${ret.id})" title="View Details">
                        <span class="icon">👁</span>
                    </button>
                    <button class="btn-action btn-edit" onclick="editReturn(${ret.id})" title="Edit">
                        <span class="icon">✎</span>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'received': '<span class="badge badge-info">Received</span>',
        'investigating': '<span class="badge badge-warning">Investigating</span>',
        'approved': '<span class="badge badge-success">Approved</span>',
        'rejected': '<span class="badge badge-danger">Rejected</span>',
        'resolved': '<span class="badge badge-success">Resolved</span>'
    };
    return badges[status] || status;
}

// Get reason badge
function getReasonBadge(reason) {
    const labels = {
        'defective': 'Defective',
        'wrong_item': 'Wrong Item',
        'damaged_shipping': 'Damaged',
        'not_as_described': 'Not Described',
        'quality_issue': 'Quality',
        'customer_error': 'Customer Error',
        'late_delivery': 'Late',
        'other': 'Other'
    };
    return `<span class="badge badge-secondary">${labels[reason] || reason}</span>`;
}

// Get resolution badge
function getResolutionBadge(resolution) {
    const badges = {
        'refund': '<span class="badge badge-success">Refund</span>',
        'replacement': '<span class="badge badge-info">Replacement</span>',
        'credit': '<span class="badge badge-warning">Credit</span>',
        'repair': '<span class="badge badge-secondary">Repair</span>',
        'no_action': '<span class="badge badge-danger">No Action</span>'
    };
    return badges[resolution] || resolution;
}

// Open return modal
function openReturnModal() {
    currentReturnId = null;
    document.getElementById('modalTitle').textContent = 'Log Customer Return';
    document.getElementById('returnForm').reset();
    document.getElementById('return_id').value = '';
    document.getElementById('orderInfoSection').style.display = 'none';
    document.getElementById('product_id').innerHTML = '<option value="">Select Order First</option>';
    
    // Generate RMA number
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    document.getElementById('rma_number').value = `RMA${year}${month}${random}`;
    
    // Set default date
    document.getElementById('return_date').value = today.toISOString().split('T')[0];
    
    loadOrders();
    document.getElementById('returnModal').classList.add('active');
}

// Close return modal
function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('active');
}

// Save return
function saveReturn(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const returnId = document.getElementById('return_id').value;
    
    const data = {
        rma_number: formData.get('rma_number'),
        order_id: formData.get('order_id'),
        product_id: formData.get('product_id'),
        quantity_returned: formData.get('quantity_returned'),
        return_reason: formData.get('return_reason'),
        customer_complaint: formData.get('customer_complaint'),
        investigation_notes: formData.get('investigation_notes'),
        return_date: formData.get('return_date'),
        status: formData.get('status'),
        resolution_type: formData.get('resolution_type') || null,
        resolution_notes: formData.get('resolution_notes'),
        refund_amount: formData.get('refund_amount') || null,
        restocking_fee: formData.get('restocking_fee') || null
    };
    
    if (returnId) {
        data.return_id = returnId;
    }
    
    const url = returnId 
        ? '../../api/defects/returns/update.php'
        : '../../api/defects/returns/create.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(returnId ? 'Return updated successfully' : 'Return logged successfully', 'success');
            closeReturnModal();
            loadReturns();
            loadSummaryStats();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving return', 'error');
    });
}

// Edit return
function editReturn(id) {
    fetch(`../../api/defects/returns/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ret = data.data;
                currentReturnId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Customer Return';
                document.getElementById('return_id').value = ret.id;
                document.getElementById('rma_number').value = ret.rma_number;
                document.getElementById('order_id').value = ret.order_id;
                document.getElementById('quantity_returned').value = ret.quantity_returned;
                document.getElementById('return_reason').value = ret.return_reason;
                document.getElementById('customer_complaint').value = ret.customer_complaint;
                document.getElementById('investigation_notes').value = ret.investigation_notes || '';
                document.getElementById('return_date').value = ret.return_date;
                document.getElementById('status').value = ret.status;
                document.getElementById('resolution_type').value = ret.resolution_type || '';
                document.getElementById('resolution_notes').value = ret.resolution_notes || '';
                document.getElementById('refund_amount').value = ret.refund_amount || '';
                document.getElementById('restocking_fee').value = ret.restocking_fee || '';
                
                // Load order products then set selected
                loadOrderProducts();
                setTimeout(() => {
                    document.getElementById('product_id').value = ret.product_id;
                }, 500);
                
                document.getElementById('returnModal').classList.add('active');
            } else {
                showAlert('Error loading return: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading return', 'error');
        });
}

// View return details
function viewReturn(id) {
    document.getElementById('viewRmaNumber').textContent = '';
    document.getElementById('returnDetailsContent').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../../api/defects/returns/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReturnDetails(data.data);
                document.getElementById('viewModal').classList.add('active');
            } else {
                showAlert('Error loading return: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading return', 'error');
        });
}

// Display return details
function displayReturnDetails(ret) {
    document.getElementById('viewRmaNumber').textContent = ret.rma_number;
    
    let html = `
        <div class="form-row">
            <div class="form-group">
                <strong>Status:</strong> ${getStatusBadge(ret.status)}
            </div>
            <div class="form-group">
                <strong>Return Date:</strong> ${formatDate(ret.return_date)}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Customer:</strong> ${escapeHtml(ret.customer_name)}
            </div>
            <div class="form-group">
                <strong>Order:</strong> ${escapeHtml(ret.order_number)}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Product:</strong> ${escapeHtml(ret.product_code)} - ${escapeHtml(ret.product_name)}
            </div>
            <div class="form-group">
                <strong>Quantity:</strong> <span class="badge badge-warning">${ret.quantity_returned}</span>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Return Reason:</strong> ${getReasonBadge(ret.return_reason)}
            </div>
            <div class="form-group">
                <strong>Resolution:</strong> ${ret.resolution_type ? getResolutionBadge(ret.resolution_type) : 'Pending'}
            </div>
        </div>
        <div class="form-group">
            <strong>Customer Complaint:</strong><br>
            ${escapeHtml(ret.customer_complaint)}
        </div>
    `;
    
    if (ret.investigation_notes) {
        html += `
            <div class="form-group">
                <strong>Investigation Notes:</strong><br>
                ${escapeHtml(ret.investigation_notes)}
            </div>
        `;
    }
    
    if (ret.resolution_notes) {
        html += `
            <div class="form-group">
                <strong>Resolution Notes:</strong><br>
                ${escapeHtml(ret.resolution_notes)}
            </div>
        `;
    }
    
    if (ret.refund_amount || ret.restocking_fee) {
        html += `<div class="form-row">`;
        if (ret.refund_amount) {
            html += `
                <div class="form-group">
                    <strong>Refund Amount:</strong> $${parseFloat(ret.refund_amount).toFixed(2)}
                </div>
            `;
        }
        if (ret.restocking_fee) {
            html += `
                <div class="form-group">
                    <strong>Restocking Fee:</strong> $${parseFloat(ret.restocking_fee).toFixed(2)}
                </div>
            `;
        }
        html += `</div>`;
    }
    
    html += `
        <div class="form-group">
            <strong>Logged By:</strong> ${escapeHtml(ret.created_by_name)} on ${formatDate(ret.created_at)}
        </div>
    `;
    
    if (ret.resolved_by_name) {
        html += `
            <div class="form-group">
                <strong>Resolved By:</strong> ${escapeHtml(ret.resolved_by_name)} on ${formatDate(ret.resolution_date)}
            </div>
        `;
    }
    
    document.getElementById('returnDetailsContent').innerHTML = html;
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '10000';
    alertDiv.style.minWidth = '300px';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
