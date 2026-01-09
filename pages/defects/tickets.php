<?php
/**
 * Barron Production Management System
 * Replacement Ticket Details Page
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

// Check permission
if (!checkPermission('defects.view')) {
    header('Location: /pages/dashboard.php');
    exit;
}

$ticket_id = $_GET['id'] ?? null;
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">🎫 Replacement Tickets</h2>
            <p class="text-muted mb-0">Manage replacement ticket approvals and processing</p>
        </div>
        <a href="/pages/defects/list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Defects
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statsCards">
        <!-- Stats will be loaded via AJAX -->
    </div>

    <!-- Filters and Tabs -->
    <div class="card mb-4">
        <div class="card-body">
            <ul class="nav nav-pills" id="statusTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-status="" onclick="filterByStatus('')">All</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="pending_approval" onclick="filterByStatus('pending_approval')">
                        Pending Approval
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="approved" onclick="filterByStatus('approved')">
                        Approved
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="rejected" onclick="filterByStatus('rejected')">
                        Rejected
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-status="completed" onclick="filterByStatus('completed')">
                        Completed
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Defect</th>
                            <th>Order/Job</th>
                            <th>Product</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ticketsTableBody">
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Tickets pagination" id="paginationContainer">
                <!-- Pagination will be loaded via AJAX -->
            </nav>
        </div>
    </div>
</div>

<!-- Ticket Details Modal -->
<div class="modal fade" id="ticketDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Replacement Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ticketDetailsContent">
                <!-- Details will be loaded via AJAX -->
            </div>
            <div class="modal-footer" id="ticketActionsFooter">
                <!-- Actions will be loaded based on status and permissions -->
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Approve Replacement Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                <input type="hidden" name="ticket_id" id="approvalTicketId">
                <div class="modal-body">
                    <p>You are about to approve this replacement ticket. The planning team will be notified.</p>
                    <div class="mb-3">
                        <label class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Replacement Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm">
                <input type="hidden" name="ticket_id" id="rejectionTicketId">
                <div class="modal-body">
                    <p>Please provide a reason for rejecting this replacement ticket.</p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" name="reason" rows="3" required
                                  placeholder="Reason is mandatory for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Modal (Planning Team) -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Replacement Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusUpdateForm">
                <input type="hidden" name="ticket_id" id="statusTicketId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="">Select status...</option>
                            <option value="replacement_processed">✅ Replacement Processed</option>
                            <option value="no_stock">⚠️ No Stock Available (Will auto-hold order)</option>
                            <option value="completed">🎉 Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Add notes about this status update..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Note:</strong> Selecting "No Stock Available" will automatically place the order on hold.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentStatus = '';

// Load statistics
function loadStatistics() {
    fetch('/api/defects/replacement_tickets.php?action=statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.statistics;
                document.getElementById('statsCards').innerHTML = `
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.total_tickets}</h3>
                                <p class="mb-0">Total Tickets</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.pending_approval}</h3>
                                <p class="mb-0">Pending Approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.approved}</h3>
                                <p class="mb-0">Approved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h3 class="mb-1">${stats.no_stock}</h3>
                                <p class="mb-0">No Stock</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
}

// Load tickets
function loadTickets(page = 1) {
    currentPage = page;
    
    const params = new URLSearchParams({
        action: 'list',
        page: page,
        status: currentStatus
    });
    
    fetch(`/api/defects/replacement_tickets.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTickets(data.tickets);
                renderPagination(data.pagination);
            }
        });
}

// Render tickets table
function renderTickets(tickets) {
    const tbody = document.getElementById('ticketsTableBody');
    
    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No tickets found</td></tr>';
        return;
    }
    
    tbody.innerHTML = tickets.map(ticket => `
        <tr>
            <td><strong>${ticket.ticket_number}</strong></td>
            <td>${ticket.defect_number}</td>
            <td>
                <div>${ticket.order_number}</div>
                <small class="text-muted">${ticket.job_number}</small>
            </td>
            <td>${ticket.product_name}</td>
            <td>${getUrgencyBadge(ticket.urgency)}</td>
            <td>${getTicketStatusBadge(ticket.status)}</td>
            <td>${new Date(ticket.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="viewTicket(${ticket.id})">
                    <i class="bi bi-eye"></i> View
                </button>
            </td>
        </tr>
    `).join('');
}

// Get urgency badge
function getUrgencyBadge(urgency) {
    const badges = {
        'urgent': '<span class="badge bg-danger">🚨 Urgent</span>',
        'high': '<span class="badge bg-warning">⚡ High</span>',
        'normal': '<span class="badge bg-info">📋 Normal</span>',
        'low': '<span class="badge bg-secondary">📝 Low</span>'
    };
    return badges[urgency] || urgency;
}

// Get ticket status badge
function getTicketStatusBadge(status) {
    const badges = {
        'pending_approval': '<span class="badge bg-warning">⏳ Pending Approval</span>',
        'approved': '<span class="badge bg-success">✅ Approved</span>',
        'rejected': '<span class="badge bg-danger">❌ Rejected</span>',
        'replacement_processed': '<span class="badge bg-info">🔄 Processing</span>',
        'no_stock': '<span class="badge bg-danger">⚠️ No Stock</span>',
        'completed': '<span class="badge bg-success">🎉 Completed</span>'
    };
    return badges[status] || status;
}

// Render pagination
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<ul class="pagination">';
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadTickets(${i}); return false;">${i}</a>
        </li>`;
    }
    
    html += '</ul>';
    container.innerHTML = html;
}

// Filter by status
function filterByStatus(status) {
    currentStatus = status;
    
    // Update active tab
    document.querySelectorAll('#statusTabs .nav-link').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-status') === status) {
            btn.classList.add('active');
        }
    });
    
    loadTickets(1);
}

// View ticket details
function viewTicket(id) {
    fetch(`/api/defects/replacement_tickets.php?action=list&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const t = data.ticket;
                
                // Render details
                document.getElementById('ticketDetailsContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Ticket Information</h6>
                            <table class="table">
                                <tr><th>Ticket Number:</th><td>${t.ticket_number}</td></tr>
                                <tr><th>Status:</th><td>${getTicketStatusBadge(t.status)}</td></tr>
                                <tr><th>Urgency:</th><td>${getUrgencyBadge(t.urgency)}</td></tr>
                                <tr><th>Created:</th><td>${new Date(t.created_at).toLocaleString()}</td></tr>
                                <tr><th>Requested By:</th><td>${t.requester_name}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Defect & Order Information</h6>
                            <table class="table">
                                <tr><th>Defect:</th><td>${t.defect_number}</td></tr>
                                <tr><th>Order:</th><td>${t.order_number}</td></tr>
                                <tr><th>Job:</th><td>${t.job_number}</td></tr>
                                <tr><th>Product:</th><td>${t.product_name}</td></tr>
                                <tr><th>Quantity:</th><td>${t.quantity}</td></tr>
                            </table>
                        </div>
                        ${t.approval_notes ? `
                        <div class="col-12 mt-3">
                            <div class="alert alert-info">
                                <strong>Approval Notes:</strong> ${t.approval_notes}
                            </div>
                        </div>
                        ` : ''}
                        ${t.rejection_reason ? `
                        <div class="col-12 mt-3">
                            <div class="alert alert-danger">
                                <strong>Rejection Reason:</strong> ${t.rejection_reason}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                // Render action buttons based on status and permissions
                let actions = '';
                
                if (t.status === 'pending_approval' && <?= checkPermission('defects.approve') ? 'true' : 'false' ?>) {
                    actions = `
                        <button class="btn btn-success" onclick="showApprovalModal(${t.id})">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                        <button class="btn btn-danger" onclick="showRejectionModal(${t.id})">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    `;
                } else if (t.status === 'approved' && <?= checkPermission('planning.edit') ? 'true' : 'false' ?>) {
                    actions = `
                        <button class="btn btn-primary" onclick="showStatusUpdateModal(${t.id})">
                            <i class="bi bi-arrow-repeat"></i> Update Status
                        </button>
                    `;
                }
                
                document.getElementById('ticketActionsFooter').innerHTML = actions;
                
                new bootstrap.Modal(document.getElementById('ticketDetailsModal')).show();
            }
        });
}

// Show approval modal
function showApprovalModal(ticketId) {
    document.getElementById('approvalTicketId').value = ticketId;
    bootstrap.Modal.getInstance(document.getElementById('ticketDetailsModal')).hide();
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

// Show rejection modal
function showRejectionModal(ticketId) {
    document.getElementById('rejectionTicketId').value = ticketId;
    bootstrap.Modal.getInstance(document.getElementById('ticketDetailsModal')).hide();
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

// Show status update modal
function showStatusUpdateModal(ticketId) {
    document.getElementById('statusTicketId').value = ticketId;
    bootstrap.Modal.getInstance(document.getElementById('ticketDetailsModal')).hide();
    new bootstrap.Modal(document.getElementById('statusUpdateModal')).show();
}

// Approval form submission
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        action: 'approve',
        id: formData.get('ticket_id'),
        notes: formData.get('notes')
    };
    
    fetch('/api/defects/replacement_tickets.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket approved successfully');
            bootstrap.Modal.getInstance(document.getElementById('approvalModal')).hide();
            loadTickets();
            loadStatistics();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Rejection form submission
document.getElementById('rejectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        action: 'reject',
        id: formData.get('ticket_id'),
        reason: formData.get('reason')
    };
    
    fetch('/api/defects/replacement_tickets.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket rejected');
            bootstrap.Modal.getInstance(document.getElementById('rejectionModal')).hide();
            loadTickets();
            loadStatistics();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Status update form submission
document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        action: 'update_status',
        id: formData.get('ticket_id'),
        status: formData.get('status'),
        notes: formData.get('notes')
    };
    
    if (data.status === 'no_stock') {
        if (!confirm('This will automatically hold the order. Continue?')) {
            return;
        }
    }
    
    fetch('/api/defects/replacement_tickets.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('statusUpdateModal')).hide();
            loadTickets();
            loadStatistics();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Initial load
loadStatistics();
loadTickets();

<?php if ($ticket_id): ?>
// If ticket_id in URL, open that ticket
viewTicket(<?= $ticket_id ?>);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
