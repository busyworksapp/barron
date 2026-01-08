// SOP Failure Tickets Management
let currentEditId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryStats();
    loadTickets();
    loadDepartments();
    loadEmployees();
    generateTicketNumber();
    
    // Set default incident date to today
    document.getElementById('incident_date').valueAsDate = new Date();
    
    // Filter event listeners
    document.getElementById('searchInput').addEventListener('input', debounce(loadTickets, 300));
    document.getElementById('statusFilter').addEventListener('change', loadTickets);
    document.getElementById('severityFilter').addEventListener('change', loadTickets);
    document.getElementById('departmentFilter').addEventListener('change', loadTickets);
    document.getElementById('dateFromFilter').addEventListener('change', loadTickets);
    
    // Form submit
    document.getElementById('ticketForm').addEventListener('submit', saveTicket);
});

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Generate ticket number
function generateTicketNumber() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    document.getElementById('ticket_number').value = `SOP${year}${month}${random}`;
}

// Load summary statistics
async function loadSummaryStats() {
    try {
        const response = await fetch('../../api/sop/tickets/stats.php');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('openTicketsCount').textContent = result.data.open_count;
            document.getElementById('resolvedTicketsCount').textContent = result.data.resolved_count;
            document.getElementById('thisMonthCount').textContent = result.data.this_month_count;
            document.getElementById('criticalCount').textContent = result.data.critical_count;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load tickets
async function loadTickets() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const severity = document.getElementById('severityFilter').value;
    const department = document.getElementById('departmentFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    
    const params = new URLSearchParams({
        search: search,
        status: status,
        severity: severity,
        department_id: department,
        date_from: dateFrom
    });
    
    try {
        const response = await fetch(`../../api/sop/tickets/list.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            displayTickets(result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading tickets:', error);
        showError('Error loading tickets');
    }
}

// Display tickets
function displayTickets(tickets) {
    const tbody = document.getElementById('ticketsTableBody');
    
    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No tickets found</td></tr>';
        return;
    }
    
    tbody.innerHTML = tickets.map(ticket => `
        <tr>
            <td><strong>${escapeHtml(ticket.ticket_number)}</strong></td>
            <td>${escapeHtml(ticket.sop_reference)}</td>
            <td>
                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${escapeHtml(ticket.failure_description)}
                </div>
            </td>
            <td>${escapeHtml(ticket.department_name)}</td>
            <td>${getSeverityBadge(ticket.severity)}</td>
            <td>${getStatusBadge(ticket.status)}</td>
            <td>${formatDate(ticket.incident_date)}</td>
            <td>${escapeHtml(ticket.reported_by_name)}</td>
            <td>
                <div class="btn-group">
                    <button class="btn-icon" onclick="viewTicket(${ticket.id})" title="View Details">👁️</button>
                    ${ticket.status !== 'closed' ? `
                        <button class="btn-icon" onclick="editTicket(${ticket.id})" title="Edit">✏️</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Get severity badge
function getSeverityBadge(severity) {
    const badges = {
        'low': '<span class="badge badge-info">Low</span>',
        'medium': '<span class="badge badge-warning">Medium</span>',
        'high': '<span class="badge badge-danger">High</span>',
        'critical': '<span class="badge badge-critical">Critical</span>'
    };
    return badges[severity] || '<span class="badge">Unknown</span>';
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'open': '<span class="badge badge-info">Open</span>',
        'investigating': '<span class="badge badge-warning">Investigating</span>',
        'action_required': '<span class="badge badge-danger">Action Required</span>',
        'resolved': '<span class="badge badge-success">Resolved</span>',
        'closed': '<span class="badge badge-secondary">Closed</span>'
    };
    return badges[status] || '<span class="badge">Unknown</span>';
}

// Load departments
async function loadDepartments() {
    try {
        const response = await fetch('../../api/master/departments/list.php');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('department_id');
            const filterSelect = document.getElementById('departmentFilter');
            
            result.data.forEach(dept => {
                select.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.name)}</option>`;
                filterSelect.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading departments:', error);
    }
}

// Load employees
async function loadEmployees() {
    try {
        const response = await fetch('../../api/master/employees/list.php');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('assigned_to');
            result.data.forEach(emp => {
                select.innerHTML += `<option value="${emp.id}">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading employees:', error);
    }
}

// Show ticket modal
function showTicketModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Log SOP Failure';
    document.getElementById('ticketForm').reset();
    generateTicketNumber();
    document.getElementById('incident_date').valueAsDate = new Date();
    document.getElementById('ticketModal').classList.add('active');
}

// Close ticket modal
function closeTicketModal() {
    document.getElementById('ticketModal').classList.remove('active');
    currentEditId = null;
}

// Edit ticket
async function editTicket(id) {
    try {
        const response = await fetch(`../../api/sop/tickets/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            currentEditId = id;
            const ticket = result.data;
            
            document.getElementById('modalTitle').textContent = 'Edit SOP Failure Ticket';
            document.getElementById('ticket_id').value = ticket.id;
            document.getElementById('ticket_number').value = ticket.ticket_number;
            document.getElementById('sop_reference').value = ticket.sop_reference;
            document.getElementById('department_id').value = ticket.department_id;
            document.getElementById('severity').value = ticket.severity;
            document.getElementById('failure_description').value = ticket.failure_description;
            document.getElementById('immediate_action').value = ticket.immediate_action || '';
            document.getElementById('incident_date').value = ticket.incident_date;
            document.getElementById('status').value = ticket.status;
            document.getElementById('root_cause').value = ticket.root_cause || '';
            document.getElementById('corrective_action').value = ticket.corrective_action || '';
            document.getElementById('assigned_to').value = ticket.assigned_to || '';
            document.getElementById('target_closure_date').value = ticket.target_closure_date || '';
            
            document.getElementById('ticketModal').classList.add('active');
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading ticket:', error);
        showError('Error loading ticket details');
    }
}

// Save ticket
async function saveTicket(e) {
    e.preventDefault();
    
    const formData = {
        ticket_id: document.getElementById('ticket_id').value,
        ticket_number: document.getElementById('ticket_number').value,
        sop_reference: document.getElementById('sop_reference').value,
        department_id: document.getElementById('department_id').value,
        severity: document.getElementById('severity').value,
        failure_description: document.getElementById('failure_description').value,
        immediate_action: document.getElementById('immediate_action').value,
        incident_date: document.getElementById('incident_date').value,
        status: document.getElementById('status').value,
        root_cause: document.getElementById('root_cause').value,
        corrective_action: document.getElementById('corrective_action').value,
        assigned_to: document.getElementById('assigned_to').value || null,
        target_closure_date: document.getElementById('target_closure_date').value || null
    };
    
    const url = formData.ticket_id 
        ? '../../api/sop/tickets/update.php'
        : '../../api/sop/tickets/create.php';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeTicketModal();
            loadTickets();
            loadSummaryStats();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error saving ticket:', error);
        showError('Error saving ticket');
    }
}

// View ticket
async function viewTicket(id) {
    try {
        const response = await fetch(`../../api/sop/tickets/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            displayTicketDetails(result.data);
            document.getElementById('viewTicketModal').classList.add('active');
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading ticket:', error);
        showError('Error loading ticket details');
    }
}

// Display ticket details
function displayTicketDetails(ticket) {
    const content = document.getElementById('ticketDetailsContent');
    content.innerHTML = `
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-label">Ticket Number:</div>
                <div class="detail-value"><strong>${escapeHtml(ticket.ticket_number)}</strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">SOP Reference:</div>
                <div class="detail-value">${escapeHtml(ticket.sop_reference)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value">${escapeHtml(ticket.department_name)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Severity:</div>
                <div class="detail-value">${getSeverityBadge(ticket.severity)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">${getStatusBadge(ticket.status)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Incident Date:</div>
                <div class="detail-value">${formatDate(ticket.incident_date)}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Failure Description:</div>
                <div class="detail-value">${escapeHtml(ticket.failure_description)}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Immediate Action:</div>
                <div class="detail-value">${escapeHtml(ticket.immediate_action || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Root Cause:</div>
                <div class="detail-value">${escapeHtml(ticket.root_cause || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Corrective Action:</div>
                <div class="detail-value">${escapeHtml(ticket.corrective_action || 'N/A')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Assigned To:</div>
                <div class="detail-value">${escapeHtml(ticket.assigned_to_name || 'Unassigned')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Target Closure:</div>
                <div class="detail-value">${ticket.target_closure_date ? formatDate(ticket.target_closure_date) : 'Not set'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Reported By:</div>
                <div class="detail-value">${escapeHtml(ticket.reported_by_name)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Reported Date:</div>
                <div class="detail-value">${formatDateTime(ticket.created_at)}</div>
            </div>
            ${ticket.closed_by_name ? `
                <div class="detail-row">
                    <div class="detail-label">Closed By:</div>
                    <div class="detail-value">${escapeHtml(ticket.closed_by_name)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Closed Date:</div>
                    <div class="detail-value">${formatDateTime(ticket.closed_date)}</div>
                </div>
            ` : ''}
        </div>
    `;
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewTicketModal').classList.remove('active');
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-GB');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert('Error: ' + message);
}
