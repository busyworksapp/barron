// Maintenance Tickets Management
let currentEditId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryStats();
    loadTickets();
    loadMachines();
    loadTechnicians();
    generateTicketNumber();
    
    // Filter event listeners
    document.getElementById('searchInput').addEventListener('input', debounce(loadTickets, 300));
    document.getElementById('statusFilter').addEventListener('change', loadTickets);
    document.getElementById('priorityFilter').addEventListener('change', loadTickets);
    document.getElementById('typeFilter').addEventListener('change', loadTickets);
    document.getElementById('machineFilter').addEventListener('change', loadTickets);
    
    // Form submit
    document.getElementById('ticketForm').addEventListener('submit', saveTicket);
});

// Debounce function
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
    document.getElementById('ticket_number').value = `MNT${year}${month}${random}`;
}

// Load summary statistics
async function loadSummaryStats() {
    try {
        const response = await fetch('../../api/maintenance/tickets/stats.php');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('openTicketsCount').textContent = result.data.open_count;
            document.getElementById('inProgressCount').textContent = result.data.in_progress_count;
            document.getElementById('completedCount').textContent = result.data.completed_count;
            document.getElementById('urgentCount').textContent = result.data.urgent_count;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load tickets
async function loadTickets() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    const type = document.getElementById('typeFilter').value;
    const machine = document.getElementById('machineFilter').value;
    
    const params = new URLSearchParams({
        search: search,
        status: status,
        priority: priority,
        maintenance_type: type,
        machine_id: machine
    });
    
    try {
        const response = await fetch(`../../api/maintenance/tickets/list.php?${params}`);
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
            <td>${escapeHtml(ticket.machine_name)}</td>
            <td>${getTypeBadge(ticket.maintenance_type)}</td>
            <td>
                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${escapeHtml(ticket.issue_description)}
                </div>
            </td>
            <td>${getPriorityBadge(ticket.priority)}</td>
            <td>${getStatusBadge(ticket.status)}</td>
            <td>${escapeHtml(ticket.assigned_to_name || 'Unassigned')}</td>
            <td>${formatDateTime(ticket.created_at)}</td>
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

// Get type badge
function getTypeBadge(type) {
    const badges = {
        'breakdown': '<span class="badge badge-danger">Breakdown</span>',
        'preventive': '<span class="badge badge-info">Preventive</span>',
        'inspection': '<span class="badge badge-warning">Inspection</span>',
        'calibration': '<span class="badge badge-primary">Calibration</span>'
    };
    return badges[type] || '<span class="badge">Unknown</span>';
}

// Get priority badge
function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge badge-secondary">Low</span>',
        'normal': '<span class="badge badge-info">Normal</span>',
        'high': '<span class="badge badge-warning">High</span>',
        'urgent': '<span class="badge badge-danger">Urgent</span>'
    };
    return badges[priority] || '<span class="badge">Unknown</span>';
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'open': '<span class="badge badge-info">Open</span>',
        'assigned': '<span class="badge badge-primary">Assigned</span>',
        'in_progress': '<span class="badge badge-warning">In Progress</span>',
        'on_hold': '<span class="badge badge-secondary">On Hold</span>',
        'completed': '<span class="badge badge-success">Completed</span>',
        'closed': '<span class="badge badge-secondary">Closed</span>'
    };
    return badges[status] || '<span class="badge">Unknown</span>';
}

// Load machines
async function loadMachines() {
    try {
        const response = await fetch('../../api/master/machines/list.php');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('machine_id');
            const filterSelect = document.getElementById('machineFilter');
            
            result.data.forEach(machine => {
                select.innerHTML += `<option value="${machine.id}">${escapeHtml(machine.machine_name)} (${escapeHtml(machine.machine_code)})</option>`;
                filterSelect.innerHTML += `<option value="${machine.id}">${escapeHtml(machine.machine_name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading machines:', error);
    }
}

// Load technicians
async function loadTechnicians() {
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
        console.error('Error loading technicians:', error);
    }
}

// Show ticket modal
function showTicketModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Create Maintenance Ticket';
    document.getElementById('ticketForm').reset();
    generateTicketNumber();
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
        const response = await fetch(`../../api/maintenance/tickets/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            currentEditId = id;
            const ticket = result.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Maintenance Ticket';
            document.getElementById('ticket_id').value = ticket.id;
            document.getElementById('ticket_number').value = ticket.ticket_number;
            document.getElementById('machine_id').value = ticket.machine_id;
            document.getElementById('maintenance_type').value = ticket.maintenance_type;
            document.getElementById('priority').value = ticket.priority;
            document.getElementById('issue_description').value = ticket.issue_description;
            document.getElementById('work_performed').value = ticket.work_performed || '';
            document.getElementById('assigned_to').value = ticket.assigned_to || '';
            document.getElementById('status').value = ticket.status;
            document.getElementById('scheduled_date').value = ticket.scheduled_date ? ticket.scheduled_date.replace(' ', 'T') : '';
            document.getElementById('completed_date').value = ticket.completed_date ? ticket.completed_date.replace(' ', 'T') : '';
            document.getElementById('downtime_hours').value = ticket.downtime_hours || '';
            document.getElementById('cost').value = ticket.cost || '';
            document.getElementById('parts_used').value = ticket.parts_used || '';
            document.getElementById('notes').value = ticket.notes || '';
            
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
        machine_id: document.getElementById('machine_id').value,
        maintenance_type: document.getElementById('maintenance_type').value,
        priority: document.getElementById('priority').value,
        issue_description: document.getElementById('issue_description').value,
        work_performed: document.getElementById('work_performed').value,
        assigned_to: document.getElementById('assigned_to').value || null,
        status: document.getElementById('status').value,
        scheduled_date: document.getElementById('scheduled_date').value || null,
        completed_date: document.getElementById('completed_date').value || null,
        downtime_hours: document.getElementById('downtime_hours').value || null,
        cost: document.getElementById('cost').value || null,
        parts_used: document.getElementById('parts_used').value,
        notes: document.getElementById('notes').value
    };
    
    const url = formData.ticket_id 
        ? '../../api/maintenance/tickets/update.php'
        : '../../api/maintenance/tickets/create.php';
    
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
        const response = await fetch(`../../api/maintenance/tickets/get.php?id=${id}`);
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
                <div class="detail-label">Machine:</div>
                <div class="detail-value">${escapeHtml(ticket.machine_name)} (${escapeHtml(ticket.machine_code)})</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Type:</div>
                <div class="detail-value">${getTypeBadge(ticket.maintenance_type)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Priority:</div>
                <div class="detail-value">${getPriorityBadge(ticket.priority)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">${getStatusBadge(ticket.status)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value">${escapeHtml(ticket.department_name)}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Issue Description:</div>
                <div class="detail-value">${escapeHtml(ticket.issue_description)}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Work Performed:</div>
                <div class="detail-value">${escapeHtml(ticket.work_performed || 'N/A')}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Parts Used:</div>
                <div class="detail-value">${escapeHtml(ticket.parts_used || 'N/A')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Assigned To:</div>
                <div class="detail-value">${escapeHtml(ticket.assigned_to_name || 'Unassigned')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Scheduled Date:</div>
                <div class="detail-value">${ticket.scheduled_date ? formatDateTime(ticket.scheduled_date) : 'Not scheduled'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Completed Date:</div>
                <div class="detail-value">${ticket.completed_date ? formatDateTime(ticket.completed_date) : 'Not completed'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Downtime:</div>
                <div class="detail-value">${ticket.downtime_hours ? ticket.downtime_hours + ' hours' : 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Cost:</div>
                <div class="detail-value">${ticket.cost ? '$' + parseFloat(ticket.cost).toFixed(2) : 'N/A'}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Notes:</div>
                <div class="detail-value">${escapeHtml(ticket.notes || 'N/A')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Created By:</div>
                <div class="detail-value">${escapeHtml(ticket.created_by_name)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Created Date:</div>
                <div class="detail-value">${formatDateTime(ticket.created_at)}</div>
            </div>
        </div>
    `;
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewTicketModal').classList.remove('active');
}

// Utility functions
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
