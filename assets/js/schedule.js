// Job Scheduling JavaScript
let orders = [];
let departments = [];
let orderItems = [];
let currentJobId = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    loadDepartments();
    loadJobs();
    
    // Setup form submission
    document.getElementById('jobForm').addEventListener('submit', saveJob);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadJobs();
        }
    });
    
    // Set default dates
    const today = new Date();
    document.getElementById('scheduled_start').valueAsDate = today;
    const endDate = new Date(today);
    endDate.setDate(endDate.getDate() + 7); // 1 week default
    document.getElementById('scheduled_end').valueAsDate = endDate;
});

// Load orders for dropdown
function loadOrders() {
    fetch('../../api/planning/orders/list.php?status=confirmed')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                orders = data.data;
                populateOrderDropdown();
            }
        })
        .catch(error => console.error('Error loading orders:', error));
}

// Populate order dropdown
function populateOrderDropdown() {
    const select = document.getElementById('order_id');
    select.innerHTML = '<option value="">Select Order</option>';
    
    orders.forEach(order => {
        select.innerHTML += `<option value="${order.id}">${escapeHtml(order.order_number)} - ${escapeHtml(order.customer_name)}</option>`;
    });
}

// Load order items when order is selected
function loadOrderItems() {
    const orderId = document.getElementById('order_id').value;
    const itemSelect = document.getElementById('order_item_id');
    
    if (!orderId) {
        itemSelect.innerHTML = '<option value="">Select Order First</option>';
        document.getElementById('orderQuantityHint').textContent = '';
        return;
    }
    
    itemSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`../../api/planning/orders/get.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                orderItems = data.data.items;
                itemSelect.innerHTML = '<option value="">Select Product</option>';
                
                orderItems.forEach(item => {
                    itemSelect.innerHTML += `<option value="${item.id}" data-quantity="${item.quantity}">${escapeHtml(item.product_code)} - ${escapeHtml(item.product_name)} (Qty: ${item.quantity})</option>`;
                });
                
                // Setup quantity hint
                itemSelect.addEventListener('change', updateQuantityHint);
            } else {
                itemSelect.innerHTML = '<option value="">Error loading items</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            itemSelect.innerHTML = '<option value="">Error loading items</option>';
        });
}

// Update quantity hint based on selected item
function updateQuantityHint() {
    const itemSelect = document.getElementById('order_item_id');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const orderQty = selectedOption.getAttribute('data-quantity');
    
    if (orderQty) {
        document.getElementById('orderQuantityHint').textContent = `Order quantity: ${orderQty}`;
        document.getElementById('quantity').max = orderQty;
    } else {
        document.getElementById('orderQuantityHint').textContent = '';
        document.getElementById('quantity').removeAttribute('max');
    }
}

// Load departments for filters and form
function loadDepartments() {
    fetch('../../api/master/departments/list.php?is_active=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                departments = data.data;
                populateDepartmentFilters();
            }
        })
        .catch(error => console.error('Error loading departments:', error));
}

// Populate department filter and select
function populateDepartmentFilters() {
    const filter = document.getElementById('departmentFilter');
    const select = document.getElementById('department_id');
    
    filter.innerHTML = '<option value="">All Departments</option>';
    select.innerHTML = '<option value="">Select Department</option>';
    
    departments.forEach(dept => {
        filter.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
        select.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
    });
}

// Load department resources (stages, machines, employees)
function loadDepartmentResources() {
    const deptId = document.getElementById('department_id').value;
    
    // Reset dependent fields
    document.getElementById('production_stage_id').innerHTML = '<option value="">Select Department First</option>';
    document.getElementById('machine_id').innerHTML = '<option value="">Select Department First</option>';
    document.getElementById('assigned_to').innerHTML = '<option value="">Select Department First</option>';
    
    if (!deptId) return;
    
    // Load production stages
    fetch(`../../api/master/departments/stages.php?department_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('production_stage_id');
                select.innerHTML = '<option value="">No Stage Required</option>';
                data.data.forEach(stage => {
                    select.innerHTML += `<option value="${stage.id}">${escapeHtml(stage.stage_name)} (Order: ${stage.stage_order})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading stages:', error));
    
    // Load machines
    fetch(`../../api/master/machines/list.php?department_id=${deptId}&status=available`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('machine_id');
                select.innerHTML = '<option value="">No Machine Required</option>';
                data.data.forEach(machine => {
                    select.innerHTML += `<option value="${machine.id}">${escapeHtml(machine.machine_name)} (${escapeHtml(machine.machine_code)})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading machines:', error));
    
    // Load employees in department
    fetch(`../../api/master/employees/list.php?department_id=${deptId}&is_active=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('assigned_to');
                select.innerHTML = '<option value="">Unassigned</option>';
                data.data.forEach(emp => {
                    select.innerHTML += `<option value="${emp.id}">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)} (${escapeHtml(emp.employee_number)})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading employees:', error));
}

// Load jobs with filters
function loadJobs() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const department = document.getElementById('departmentFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    
    let url = '../../api/planning/jobs/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (status) url += `status=${status}&`;
    if (department) url += `department_id=${department}&`;
    if (dateFrom) url += `date_from=${dateFrom}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayJobs(data.data);
            } else {
                showAlert('Error loading jobs: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading jobs', 'error');
        });
}

// Display jobs in table
function displayJobs(jobs) {
    const tbody = document.getElementById('jobsTableBody');
    
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No jobs found</td></tr>';
        return;
    }
    
    let html = '';
    jobs.forEach(job => {
        const statusBadge = getStatusBadge(job.status);
        const priorityBadge = job.priority !== 'normal' ? getPriorityBadge(job.priority) : '';
        
        // Check if overdue
        let dueDateDisplay = formatDate(job.scheduled_end);
        if (job.status !== 'completed' && job.status !== 'cancelled') {
            const today = new Date();
            const dueDate = new Date(job.scheduled_end);
            if (dueDate < today) {
                dueDateDisplay = `<span class="badge badge-danger">${dueDateDisplay}</span>`;
            }
        }
        
        html += `
            <tr>
                <td>${escapeHtml(job.job_number)} ${priorityBadge}</td>
                <td>${escapeHtml(job.order_number)}</td>
                <td>${escapeHtml(job.product_code)} - ${escapeHtml(job.product_name)}</td>
                <td>${escapeHtml(job.department_name)}</td>
                <td>${job.quantity}</td>
                <td>${job.assigned_to_name ? escapeHtml(job.assigned_to_name) : '<span class="text-muted">Unassigned</span>'}</td>
                <td>${formatDate(job.scheduled_start)}</td>
                <td>${dueDateDisplay}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-secondary" onclick="viewJob(${job.id})" title="View Details">
                        <span class="icon">👁</span>
                    </button>
                    <button class="btn-action btn-edit" onclick="editJob(${job.id})" title="Edit">
                        <span class="icon">✎</span>
                    </button>
                    <button class="btn-action btn-danger" onclick="deleteJob(${job.id}, '${escapeHtml(job.job_number)}')" title="Delete">
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
        'scheduled': '<span class="badge badge-info">Scheduled</span>',
        'in_progress': '<span class="badge badge-warning">In Progress</span>',
        'completed': '<span class="badge badge-success">Completed</span>',
        'on_hold': '<span class="badge badge-secondary">On Hold</span>',
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

// Open job modal for adding
function openJobModal() {
    currentJobId = null;
    document.getElementById('modalTitle').textContent = 'Schedule Job';
    document.getElementById('jobForm').reset();
    document.getElementById('job_id').value = '';
    
    // Generate job number
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    document.getElementById('job_number').value = `JOB${year}${month}${random}`;
    
    // Set default dates
    document.getElementById('scheduled_start').valueAsDate = today;
    const endDate = new Date(today);
    endDate.setDate(endDate.getDate() + 7);
    document.getElementById('scheduled_end').valueAsDate = endDate;
    
    // Reset dropdowns
    document.getElementById('order_item_id').innerHTML = '<option value="">Select Order First</option>';
    document.getElementById('production_stage_id').innerHTML = '<option value="">Select Department First</option>';
    document.getElementById('machine_id').innerHTML = '<option value="">Select Department First</option>';
    document.getElementById('assigned_to').innerHTML = '<option value="">Select Department First</option>';
    document.getElementById('orderQuantityHint').textContent = '';
    
    document.getElementById('jobModal').classList.add('active');
}

// Close job modal
function closeJobModal() {
    document.getElementById('jobModal').classList.remove('active');
}

// Save job
function saveJob(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const jobId = document.getElementById('job_id').value;
    
    const data = {
        job_number: formData.get('job_number'),
        order_id: formData.get('order_id'),
        order_item_id: formData.get('order_item_id'),
        quantity: formData.get('quantity'),
        department_id: formData.get('department_id'),
        production_stage_id: formData.get('production_stage_id') || null,
        machine_id: formData.get('machine_id') || null,
        assigned_to: formData.get('assigned_to') || null,
        scheduled_start: formData.get('scheduled_start'),
        scheduled_end: formData.get('scheduled_end'),
        job_notes: formData.get('job_notes'),
        status: formData.get('status'),
        priority: formData.get('priority')
    };
    
    if (jobId) {
        data.job_id = jobId;
    }
    
    const url = jobId 
        ? '../../api/planning/jobs/update.php'
        : '../../api/planning/jobs/create.php';
    
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
            showAlert(jobId ? 'Job updated successfully' : 'Job created successfully', 'success');
            closeJobModal();
            loadJobs();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving job', 'error');
    });
}

// Edit job
function editJob(id) {
    fetch(`../../api/planning/jobs/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const job = data.data;
                currentJobId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Job';
                document.getElementById('job_id').value = job.id;
                document.getElementById('job_number').value = job.job_number;
                document.getElementById('order_id').value = job.order_id;
                
                // Load order items then set selected
                loadOrderItems();
                setTimeout(() => {
                    document.getElementById('order_item_id').value = job.order_item_id;
                    updateQuantityHint();
                }, 500);
                
                document.getElementById('quantity').value = job.quantity;
                document.getElementById('department_id').value = job.department_id;
                
                // Load department resources then set selected
                loadDepartmentResources();
                setTimeout(() => {
                    document.getElementById('production_stage_id').value = job.production_stage_id || '';
                    document.getElementById('machine_id').value = job.machine_id || '';
                    document.getElementById('assigned_to').value = job.assigned_to || '';
                }, 500);
                
                document.getElementById('scheduled_start').value = job.scheduled_start;
                document.getElementById('scheduled_end').value = job.scheduled_end;
                document.getElementById('job_notes').value = job.job_notes || '';
                document.getElementById('status').value = job.status;
                document.getElementById('priority').value = job.priority;
                
                document.getElementById('jobModal').classList.add('active');
            } else {
                showAlert('Error loading job: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading job', 'error');
        });
}

// View job details
function viewJob(id) {
    document.getElementById('viewJobNumber').textContent = '';
    document.getElementById('jobDetailsContent').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../../api/planning/jobs/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayJobDetails(data.data);
                document.getElementById('viewJobModal').classList.add('active');
            } else {
                showAlert('Error loading job: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading job', 'error');
        });
}

// Display job details
function displayJobDetails(job) {
    document.getElementById('viewJobNumber').textContent = job.job_number;
    
    let html = `
        <div class="form-row">
            <div class="form-group">
                <strong>Order:</strong> ${escapeHtml(job.order_number)}
            </div>
            <div class="form-group">
                <strong>Status:</strong> ${getStatusBadge(job.status)}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Product:</strong> ${escapeHtml(job.product_code)} - ${escapeHtml(job.product_name)}
            </div>
            <div class="form-group">
                <strong>Quantity:</strong> ${job.quantity}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Department:</strong> ${escapeHtml(job.department_name)}
            </div>
            <div class="form-group">
                <strong>Stage:</strong> ${job.stage_name ? escapeHtml(job.stage_name) : '-'}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Machine:</strong> ${job.machine_name ? escapeHtml(job.machine_name) : '-'}
            </div>
            <div class="form-group">
                <strong>Assigned To:</strong> ${job.assigned_to_name ? escapeHtml(job.assigned_to_name) : '-'}
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <strong>Start Date:</strong> ${formatDate(job.scheduled_start)}
            </div>
            <div class="form-group">
                <strong>End Date:</strong> ${formatDate(job.scheduled_end)}
            </div>
        </div>
    `;
    
    if (job.job_notes) {
        html += `<div class="form-group"><strong>Notes:</strong> ${escapeHtml(job.job_notes)}</div>`;
    }
    
    html += `<div class="form-group"><strong>Created By:</strong> ${escapeHtml(job.created_by_name)} on ${formatDate(job.created_at)}</div>`;
    
    document.getElementById('jobDetailsContent').innerHTML = html;
}

// Close view job modal
function closeViewJobModal() {
    document.getElementById('viewJobModal').classList.remove('active');
}

// Delete job
function deleteJob(id, jobNumber) {
    if (!confirm(`Are you sure you want to delete job "${jobNumber}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch('../../api/planning/jobs/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ job_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Job deleted successfully', 'success');
            loadJobs();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting job', 'error');
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
