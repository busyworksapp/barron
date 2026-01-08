// Orders Management JavaScript
let products = [];
let orderItemIndex = 1;
let currentOrderId = null;

// Load orders on page load
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadOrders();
    
    // Setup form submission
    document.getElementById('orderForm').addEventListener('submit', saveOrder);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadOrders();
        }
    });
    
    // Set default dates
    const today = new Date();
    document.getElementById('order_date').valueAsDate = today;
    const dueDate = new Date(today);
    dueDate.setDate(dueDate.getDate() + 14); // 2 weeks default
    document.getElementById('due_date').valueAsDate = dueDate;
});

// Load products for dropdowns
function loadProducts() {
    fetch('../../api/master/products/list.php?is_active=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                products = data.data;
                populateProductDropdowns();
            }
        })
        .catch(error => console.error('Error loading products:', error));
}

// Populate product dropdowns
function populateProductDropdowns() {
    const selects = document.querySelectorAll('.item-product');
    selects.forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">Select Product</option>';
        products.forEach(product => {
            select.innerHTML += `<option value="${product.id}">${escapeHtml(product.product_code)} - ${escapeHtml(product.product_name)}</option>`;
        });
        if (currentValue) {
            select.value = currentValue;
        }
    });
}

// Load orders with filters
function loadOrders() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    
    let url = '../../api/planning/orders/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (status) url += `status=${status}&`;
    if (dateFrom) url += `date_from=${dateFrom}&`;
    if (dateTo) url += `date_to=${dateTo}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrders(data.data);
            } else {
                showAlert('Error loading orders: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading orders', 'error');
        });
}

// Display orders in table
function displayOrders(orders) {
    const tbody = document.getElementById('ordersTableBody');
    
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No orders found</td></tr>';
        return;
    }
    
    let html = '';
    orders.forEach(order => {
        const statusBadge = getStatusBadge(order.status);
        const priorityBadge = order.priority !== 'normal' ? getPriorityBadge(order.priority) : '';
        
        // Check if overdue
        let dueDateDisplay = formatDate(order.due_date);
        if (order.status !== 'completed' && order.status !== 'cancelled') {
            const today = new Date();
            const dueDate = new Date(order.due_date);
            if (dueDate < today) {
                dueDateDisplay = `<span class="badge badge-danger">${dueDateDisplay} (Overdue)</span>`;
            }
        }
        
        html += `
            <tr>
                <td>${escapeHtml(order.order_number)} ${priorityBadge}</td>
                <td>${escapeHtml(order.customer_name)}</td>
                <td>${formatDate(order.order_date)}</td>
                <td>${dueDateDisplay}</td>
                <td>${order.item_count} item${order.item_count !== 1 ? 's' : ''}</td>
                <td>${order.total_quantity}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-secondary" onclick="viewOrder(${order.id})" title="View Details">
                        <span class="icon">👁</span>
                    </button>
                    <button class="btn-action btn-edit" onclick="editOrder(${order.id})" title="Edit">
                        <span class="icon">✎</span>
                    </button>
                    <button class="btn-action btn-danger" onclick="deleteOrder(${order.id}, '${escapeHtml(order.order_number)}')" title="Delete">
                        <span class="icon">🗑</span>
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
        'pending': '<span class="badge badge-warning">Pending</span>',
        'confirmed': '<span class="badge badge-info">Confirmed</span>',
        'in_progress': '<span class="badge badge-primary">In Progress</span>',
        'completed': '<span class="badge badge-success">Completed</span>',
        'cancelled': '<span class="badge badge-danger">Cancelled</span>'
    };
    return badges[status] || status;
}

// Get priority badge
function getPriorityBadge(priority) {
    const badges = {
        'high': '<span class="badge badge-warning">High</span>',
        'urgent': '<span class="badge badge-danger">Urgent</span>'
    };
    return badges[priority] || '';
}

// Open order modal for adding
function openOrderModal() {
    currentOrderId = null;
    document.getElementById('modalTitle').textContent = 'New Order';
    document.getElementById('orderForm').reset();
    document.getElementById('order_id').value = '';
    
    // Set default dates
    const today = new Date();
    document.getElementById('order_date').valueAsDate = today;
    const dueDate = new Date(today);
    dueDate.setDate(dueDate.getDate() + 14);
    document.getElementById('due_date').valueAsDate = dueDate;
    
    // Reset to one empty item
    document.getElementById('orderItemsContainer').innerHTML = `
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
    `;
    orderItemIndex = 1;
    populateProductDropdowns();
    
    document.getElementById('orderModal').classList.add('active');
}

// Close order modal
function closeOrderModal() {
    document.getElementById('orderModal').classList.remove('active');
}

// Add order item row
function addOrderItem() {
    const container = document.getElementById('orderItemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'order-item-row';
    newRow.setAttribute('data-index', orderItemIndex);
    newRow.innerHTML = `
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
                <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(${orderItemIndex})" title="Remove">
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
    `;
    container.appendChild(newRow);
    populateProductDropdowns();
    orderItemIndex++;
}

// Remove order item row
function removeOrderItem(index) {
    const row = document.querySelector(`.order-item-row[data-index="${index}"]`);
    if (row) {
        const container = document.getElementById('orderItemsContainer');
        if (container.children.length > 1) {
            row.remove();
        } else {
            showAlert('Order must have at least one item', 'error');
        }
    }
}

// Get order items from form
function getOrderItems() {
    const items = [];
    const rows = document.querySelectorAll('.order-item-row');
    
    rows.forEach(row => {
        const productId = row.querySelector('.item-product').value;
        const quantity = row.querySelector('.item-quantity').value;
        const price = row.querySelector('.item-price').value;
        const notes = row.querySelector('.item-notes').value;
        
        if (productId && quantity) {
            items.push({
                product_id: parseInt(productId),
                quantity: parseInt(quantity),
                unit_price: price ? parseFloat(price) : null,
                notes: notes || null
            });
        }
    });
    
    return items;
}

// Save order
function saveOrder(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const orderId = document.getElementById('order_id').value;
    const items = getOrderItems();
    
    if (items.length === 0) {
        showAlert('Please add at least one order item', 'error');
        return;
    }
    
    const data = {
        order_number: formData.get('order_number'),
        customer_name: formData.get('customer_name'),
        customer_ref: formData.get('customer_ref'),
        po_number: formData.get('po_number'),
        order_date: formData.get('order_date'),
        due_date: formData.get('due_date'),
        notes: formData.get('notes'),
        status: formData.get('status'),
        priority: formData.get('priority'),
        items: items
    };
    
    if (orderId) {
        data.order_id = orderId;
    }
    
    const url = orderId 
        ? '../../api/planning/orders/update.php'
        : '../../api/planning/orders/create.php';
    
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
            showAlert(orderId ? 'Order updated successfully' : 'Order created successfully', 'success');
            closeOrderModal();
            loadOrders();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving order', 'error');
    });
}

// Edit order (continued in next file due to length)
function editOrder(id) {
    fetch(`../../api/planning/orders/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.data;
                currentOrderId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Order';
                document.getElementById('order_id').value = order.id;
                document.getElementById('order_number').value = order.order_number;
                document.getElementById('customer_name').value = order.customer_name;
                document.getElementById('customer_ref').value = order.customer_ref || '';
                document.getElementById('po_number').value = order.po_number || '';
                document.getElementById('order_date').value = order.order_date;
                document.getElementById('due_date').value = order.due_date;
                document.getElementById('notes').value = order.notes || '';
                document.getElementById('status').value = order.status;
                document.getElementById('priority').value = order.priority;
                
                // Load order items
                loadOrderItemsForEdit(order.items);
                
                document.getElementById('orderModal').classList.add('active');
            } else {
                showAlert('Error loading order: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading order', 'error');
        });
}

// Load order items for editing
function loadOrderItemsForEdit(items) {
    const container = document.getElementById('orderItemsContainer');
    container.innerHTML = '';
    orderItemIndex = 0;
    
    if (!items || items.length === 0) {
        addOrderItem();
    } else {
        items.forEach(item => {
            const newRow = document.createElement('div');
            newRow.className = 'order-item-row';
            newRow.setAttribute('data-index', orderItemIndex);
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label required">Product</label>
                        <select class="form-control item-product" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label required">Quantity</label>
                        <input type="number" class="form-control item-quantity" min="1" value="${item.quantity}" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Unit Price</label>
                        <input type="number" class="form-control item-price" step="0.01" min="0" value="${item.unit_price || ''}">
                    </div>
                    <div class="form-group" style="flex: 0 0 auto; padding-top: 28px;">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(${orderItemIndex})" title="Remove">
                            <span class="icon">✖</span>
                        </button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Item Notes</label>
                        <input type="text" class="form-control item-notes" maxlength="500" value="${item.notes || ''}">
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            
            // Set product after adding to DOM
            const select = newRow.querySelector('.item-product');
            populateProductDropdowns();
            select.value = item.product_id;
            
            orderItemIndex++;
        });
    }
}

// View order details
function viewOrder(id) {
    document.getElementById('viewOrderNumber').textContent = '';
    document.getElementById('orderDetailsContent').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../../api/planning/orders/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.data);
                document.getElementById('viewOrderModal').classList.add('active');
            } else {
                showAlert('Error loading order: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading order', 'error');
        });
}

// Display order details
function displayOrderDetails(order) {
    document.getElementById('viewOrderNumber').textContent = order.order_number;
    
    let html = `
        <div class="form-row">
            <div class="form-group">
                <strong>Customer:</strong> ${escapeHtml(order.customer_name)}
            </div>
            <div class="form-group">
                <strong>Status:</strong> ${getStatusBadge(order.status)}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Order Date:</strong> ${formatDate(order.order_date)}
            </div>
            <div class="form-group">
                <strong>Due Date:</strong> ${formatDate(order.due_date)}
            </div>
        </div>
    `;
    
    if (order.customer_ref) {
        html += `<div class="form-group"><strong>Customer Reference:</strong> ${escapeHtml(order.customer_ref)}</div>`;
    }
    if (order.po_number) {
        html += `<div class="form-group"><strong>PO Number:</strong> ${escapeHtml(order.po_number)}</div>`;
    }
    if (order.notes) {
        html += `<div class="form-group"><strong>Notes:</strong> ${escapeHtml(order.notes)}</div>`;
    }
    
    html += '<hr><h3>Order Items</h3>';
    html += '<table class="table table-striped"><thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th><th>Notes</th></tr></thead><tbody>';
    
    let grandTotal = 0;
    order.items.forEach(item => {
        const total = item.unit_price ? (item.quantity * item.unit_price) : 0;
        grandTotal += total;
        html += `
            <tr>
                <td>${escapeHtml(item.product_code)} - ${escapeHtml(item.product_name)}</td>
                <td>${item.quantity}</td>
                <td>${item.unit_price ? 'R' + parseFloat(item.unit_price).toFixed(2) : '-'}</td>
                <td>${item.unit_price ? 'R' + total.toFixed(2) : '-'}</td>
                <td>${item.notes ? escapeHtml(item.notes) : '-'}</td>
            </tr>
        `;
    });
    
    if (grandTotal > 0) {
        html += `<tr><td colspan="3" style="text-align: right;"><strong>Grand Total:</strong></td><td colspan="2"><strong>R${grandTotal.toFixed(2)}</strong></td></tr>`;
    }
    
    html += '</tbody></table>';
    
    document.getElementById('orderDetailsContent').innerHTML = html;
}

// Close view order modal
function closeViewOrderModal() {
    document.getElementById('viewOrderModal').classList.remove('active');
}

// Delete order
function deleteOrder(id, orderNumber) {
    if (!confirm(`Are you sure you want to delete order "${orderNumber}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch('../../api/planning/orders/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Order deleted successfully', 'success');
            loadOrders();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting order', 'error');
    });
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
