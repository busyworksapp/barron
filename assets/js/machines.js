// Machines Management JavaScript
let departments = [];
let currentMachineId = null;

// Load machines on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDepartments();
    loadMachines();
    
    // Setup form submission
    document.getElementById('machineForm').addEventListener('submit', saveMachine);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadMachines();
        }
    });
    
    // Auto-calculate next maintenance date when interval changes
    document.getElementById('maintenance_interval_days').addEventListener('input', calculateNextMaintenance);
    document.getElementById('last_maintenance_date').addEventListener('change', calculateNextMaintenance);
});

// Load departments for filters and form
function loadDepartments() {
    fetch('../../api/master/departments/list.php')
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
        if (dept.is_active == 1) {
            filter.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
            select.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
        }
    });
}

// Calculate next maintenance date based on last maintenance and interval
function calculateNextMaintenance() {
    const lastDate = document.getElementById('last_maintenance_date').value;
    const interval = parseInt(document.getElementById('maintenance_interval_days').value);
    
    if (lastDate && interval > 0) {
        const date = new Date(lastDate);
        date.setDate(date.getDate() + interval);
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        document.getElementById('next_maintenance_date').value = `${year}-${month}-${day}`;
    }
}

// Load machines with filters
function loadMachines() {
    const search = document.getElementById('searchInput').value;
    const department = document.getElementById('departmentFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = '../../api/master/machines/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (department) url += `department_id=${department}&`;
    if (status) url += `status=${status}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMachines(data.data);
            } else {
                showAlert('Error loading machines: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading machines', 'error');
        });
}

// Display machines in table
function displayMachines(machines) {
    const tbody = document.getElementById('machinesTableBody');
    
    if (machines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No machines found</td></tr>';
        return;
    }
    
    let html = '';
    machines.forEach(machine => {
        const statusBadge = getStatusBadge(machine.status);
        const lastMaint = machine.last_maintenance_date ? formatDate(machine.last_maintenance_date) : '-';
        const nextMaint = machine.next_maintenance_date ? formatDate(machine.next_maintenance_date) : '-';
        
        // Check if maintenance is overdue
        let nextMaintDisplay = nextMaint;
        if (machine.next_maintenance_date) {
            const today = new Date();
            const nextDate = new Date(machine.next_maintenance_date);
            if (nextDate < today) {
                nextMaintDisplay = `<span class="badge badge-danger">${nextMaint} (Overdue)</span>`;
            } else {
                const daysUntil = Math.ceil((nextDate - today) / (1000 * 60 * 60 * 24));
                if (daysUntil <= 7) {
                    nextMaintDisplay = `<span class="badge badge-warning">${nextMaint} (${daysUntil}d)</span>`;
                }
            }
        }
        
        html += `
            <tr>
                <td>${escapeHtml(machine.machine_code)}</td>
                <td>${escapeHtml(machine.machine_name)}</td>
                <td>${machine.machine_number ? escapeHtml(machine.machine_number) : '-'}</td>
                <td>${machine.department_name ? escapeHtml(machine.department_name) : '-'}</td>
                <td>${statusBadge}</td>
                <td>${lastMaint}</td>
                <td>${nextMaintDisplay}</td>
                <td class="actions">
                    <button class="btn-action btn-edit" onclick="editMachine(${machine.id})" title="Edit">
                        <span class="icon">✎</span>
                    </button>
                    <button class="btn-action btn-secondary" onclick="viewHistory(${machine.id}, '${escapeHtml(machine.machine_name)}')" title="Maintenance History">
                        <span class="icon">📋</span>
                    </button>
                    <button class="btn-action btn-danger" onclick="deleteMachine(${machine.id}, '${escapeHtml(machine.machine_name)}')" title="Delete">
                        <span class="icon">🗑</span>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get status badge HTML
function getStatusBadge(status) {
    const badges = {
        'available': '<span class="badge badge-success">Available</span>',
        'in_use': '<span class="badge badge-info">In Use</span>',
        'maintenance': '<span class="badge badge-warning">Maintenance</span>',
        'down': '<span class="badge badge-danger">Down</span>'
    };
    return badges[status] || status;
}

// Open machine modal for adding
function openMachineModal() {
    currentMachineId = null;
    document.getElementById('modalTitle').textContent = 'Add Machine';
    document.getElementById('machineForm').reset();
    document.getElementById('machine_id').value = '';
    document.getElementById('status').value = 'available';
    
    document.getElementById('machineModal').classList.add('active');
}

// Close machine modal
function closeMachineModal() {
    document.getElementById('machineModal').classList.remove('active');
}

// Save machine
function saveMachine(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const machineId = document.getElementById('machine_id').value;
    
    const data = {
        machine_code: formData.get('machine_code'),
        machine_name: formData.get('machine_name'),
        machine_number: formData.get('machine_number'),
        description: formData.get('description'),
        department_id: formData.get('department_id'),
        status: formData.get('status'),
        last_maintenance_date: formData.get('last_maintenance_date'),
        next_maintenance_date: formData.get('next_maintenance_date'),
        maintenance_interval_days: formData.get('maintenance_interval_days'),
        purchase_date: formData.get('purchase_date'),
        manufacturer: formData.get('manufacturer'),
        model: formData.get('model'),
        serial_number: formData.get('serial_number'),
        notes: formData.get('notes')
    };
    
    if (machineId) {
        data.machine_id = machineId;
    }
    
    const url = machineId 
        ? '../../api/master/machines/update.php'
        : '../../api/master/machines/create.php';
    
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
            showAlert(machineId ? 'Machine updated successfully' : 'Machine created successfully', 'success');
            closeMachineModal();
            loadMachines();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving machine', 'error');
    });
}

// Edit machine
function editMachine(id) {
    fetch(`../../api/master/machines/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const machine = data.data;
                currentMachineId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Machine';
                document.getElementById('machine_id').value = machine.id;
                document.getElementById('machine_code').value = machine.machine_code;
                document.getElementById('machine_name').value = machine.machine_name;
                document.getElementById('machine_number').value = machine.machine_number || '';
                document.getElementById('description').value = machine.description || '';
                document.getElementById('department_id').value = machine.department_id || '';
                document.getElementById('status').value = machine.status;
                document.getElementById('last_maintenance_date').value = machine.last_maintenance_date || '';
                document.getElementById('next_maintenance_date').value = machine.next_maintenance_date || '';
                document.getElementById('maintenance_interval_days').value = machine.maintenance_interval_days || '';
                document.getElementById('purchase_date').value = machine.purchase_date || '';
                document.getElementById('manufacturer').value = machine.manufacturer || '';
                document.getElementById('model').value = machine.model || '';
                document.getElementById('serial_number').value = machine.serial_number || '';
                document.getElementById('notes').value = machine.notes || '';
                
                document.getElementById('machineModal').classList.add('active');
            } else {
                showAlert('Error loading machine: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading machine', 'error');
        });
}

// Delete machine
function deleteMachine(id, name) {
    if (!confirm(`Are you sure you want to delete machine "${name}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch('../../api/master/machines/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ machine_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Machine deleted successfully', 'success');
            loadMachines();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting machine', 'error');
    });
}

// View maintenance history
function viewHistory(id, name) {
    document.getElementById('historyMachineName').textContent = name;
    document.getElementById('history_machine_id').value = id;
    
    fetch(`../../api/master/machines/history.php?machine_id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHistory(data.data);
                document.getElementById('historyModal').classList.add('active');
            } else {
                showAlert('Error loading history: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading history', 'error');
        });
}

// Display maintenance history
function displayHistory(tickets) {
    const tbody = document.getElementById('historyTableBody');
    
    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No maintenance history found</td></tr>';
        return;
    }
    
    let html = '';
    tickets.forEach(ticket => {
        const typeBadge = ticket.ticket_type === 'preventive' 
            ? '<span class="badge badge-info">Preventive</span>'
            : '<span class="badge badge-warning">Corrective</span>';
        
        const priorityBadge = getPriorityBadge(ticket.priority);
        const statusBadge = getTicketStatusBadge(ticket.status);
        
        html += `
            <tr>
                <td>${escapeHtml(ticket.ticket_number)}</td>
                <td>${typeBadge}</td>
                <td>${priorityBadge}</td>
                <td>${statusBadge}</td>
                <td>${ticket.reported_by_name ? escapeHtml(ticket.reported_by_name) : '-'}</td>
                <td>${ticket.assigned_to_name ? escapeHtml(ticket.assigned_to_name) : '-'}</td>
                <td>${formatDate(ticket.created_at)}</td>
                <td>${ticket.completed_at ? formatDate(ticket.completed_at) : '-'}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get priority badge
function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge badge-info">Low</span>',
        'normal': '<span class="badge badge-secondary">Normal</span>',
        'high': '<span class="badge badge-warning">High</span>',
        'critical': '<span class="badge badge-danger">Critical</span>'
    };
    return badges[priority] || priority;
}

// Get ticket status badge
function getTicketStatusBadge(status) {
    const badges = {
        'open': '<span class="badge badge-info">Open</span>',
        'in_progress': '<span class="badge badge-warning">In Progress</span>',
        'on_hold': '<span class="badge badge-secondary">On Hold</span>',
        'completed': '<span class="badge badge-success">Completed</span>',
        'cancelled': '<span class="badge badge-danger">Cancelled</span>'
    };
    return badges[status] || status;
}

// Close history modal
function closeHistoryModal() {
    document.getElementById('historyModal').classList.remove('active');
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
