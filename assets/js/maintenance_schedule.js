// Preventive Maintenance Schedule Management
let currentEditId = null;
let currentViewId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryStats();
    loadSchedules();
    loadMachines();
    loadTechnicians();
    
    // Filter event listeners
    document.getElementById('searchInput').addEventListener('input', debounce(loadSchedules, 300));
    document.getElementById('statusFilter').addEventListener('change', loadSchedules);
    document.getElementById('frequencyFilter').addEventListener('change', loadSchedules);
    document.getElementById('machineFilter').addEventListener('change', loadSchedules);
    
    // Form submit
    document.getElementById('scheduleForm').addEventListener('submit', saveSchedule);
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

// Load summary statistics
async function loadSummaryStats() {
    try {
        const response = await fetch('../../api/maintenance/schedule/stats.php');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('activeSchedulesCount').textContent = result.data.active_count;
            document.getElementById('overdueCount').textContent = result.data.overdue_count;
            document.getElementById('dueThisWeekCount').textContent = result.data.due_this_week_count;
            document.getElementById('completedThisMonthCount').textContent = result.data.completed_this_month_count;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load schedules
async function loadSchedules() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const frequency = document.getElementById('frequencyFilter').value;
    const machine = document.getElementById('machineFilter').value;
    
    const params = new URLSearchParams({
        search: search,
        status: status,
        frequency: frequency,
        machine_id: machine
    });
    
    try {
        const response = await fetch(`../../api/maintenance/schedule/list.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            displaySchedules(result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
        showError('Error loading schedules');
    }
}

// Display schedules
function displaySchedules(schedules) {
    const tbody = document.getElementById('schedulesTableBody');
    
    if (schedules.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No schedules found</td></tr>';
        return;
    }
    
    tbody.innerHTML = schedules.map(schedule => {
        const nextDue = new Date(schedule.next_due_date);
        const today = new Date();
        const isOverdue = nextDue < today && schedule.status === 'active';
        const isDueSoon = nextDue <= new Date(today.getTime() + 7*24*60*60*1000) && nextDue >= today && schedule.status === 'active';
        
        return `
            <tr ${isOverdue ? 'style="background-color: #fff5f5;"' : (isDueSoon ? 'style="background-color: #fff9db;"' : '')}>
                <td><strong>${escapeHtml(schedule.task_name)}</strong></td>
                <td>${escapeHtml(schedule.machine_name)}</td>
                <td>${getFrequencyBadge(schedule.frequency)}</td>
                <td>${schedule.last_performed_date ? formatDate(schedule.last_performed_date) : 'Never'}</td>
                <td>
                    ${formatDate(schedule.next_due_date)}
                    ${isOverdue ? '<span style="color: red; font-weight: bold;"> ⚠️ OVERDUE</span>' : ''}
                    ${isDueSoon && !isOverdue ? '<span style="color: orange; font-weight: bold;"> 🔔 Due Soon</span>' : ''}
                </td>
                <td>${escapeHtml(schedule.assigned_to_name || 'Unassigned')}</td>
                <td>${getStatusBadge(schedule.status)}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn-icon" onclick="viewSchedule(${schedule.id})" title="View Details">👁️</button>
                        <button class="btn-icon" onclick="editSchedule(${schedule.id})" title="Edit">✏️</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Get frequency badge
function getFrequencyBadge(frequency) {
    const badges = {
        'daily': '<span class="badge badge-info">Daily</span>',
        'weekly': '<span class="badge badge-primary">Weekly</span>',
        'monthly': '<span class="badge badge-warning">Monthly</span>',
        'quarterly': '<span class="badge badge-danger">Quarterly</span>',
        'semi_annual': '<span class="badge badge-secondary">Semi-Annual</span>',
        'annual': '<span class="badge badge-success">Annual</span>'
    };
    return badges[frequency] || '<span class="badge">Unknown</span>';
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge badge-success">Active</span>',
        'inactive': '<span class="badge badge-secondary">Inactive</span>'
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

// Show schedule modal
function showScheduleModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Add Preventive Maintenance Schedule';
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleModal').classList.add('active');
}

// Close schedule modal
function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('active');
    currentEditId = null;
}

// Edit schedule
async function editSchedule(id) {
    try {
        const response = await fetch(`../../api/maintenance/schedule/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            currentEditId = id;
            const schedule = result.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Preventive Maintenance Schedule';
            document.getElementById('schedule_id').value = schedule.id;
            document.getElementById('task_name').value = schedule.task_name;
            document.getElementById('machine_id').value = schedule.machine_id;
            document.getElementById('task_description').value = schedule.task_description;
            document.getElementById('frequency').value = schedule.frequency;
            document.getElementById('estimated_duration').value = schedule.estimated_duration;
            document.getElementById('assigned_to').value = schedule.assigned_to || '';
            document.getElementById('next_due_date').value = schedule.next_due_date;
            document.getElementById('checklist_items').value = schedule.checklist_items || '';
            document.getElementById('status').value = schedule.status;
            document.getElementById('last_performed_date').value = schedule.last_performed_date || '';
            document.getElementById('notes').value = schedule.notes || '';
            
            document.getElementById('scheduleModal').classList.add('active');
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading schedule:', error);
        showError('Error loading schedule details');
    }
}

// Save schedule
async function saveSchedule(e) {
    e.preventDefault();
    
    const formData = {
        schedule_id: document.getElementById('schedule_id').value,
        task_name: document.getElementById('task_name').value,
        machine_id: document.getElementById('machine_id').value,
        task_description: document.getElementById('task_description').value,
        frequency: document.getElementById('frequency').value,
        estimated_duration: document.getElementById('estimated_duration').value,
        assigned_to: document.getElementById('assigned_to').value || null,
        next_due_date: document.getElementById('next_due_date').value,
        checklist_items: document.getElementById('checklist_items').value,
        status: document.getElementById('status').value,
        last_performed_date: document.getElementById('last_performed_date').value || null,
        notes: document.getElementById('notes').value
    };
    
    const url = formData.schedule_id 
        ? '../../api/maintenance/schedule/update.php'
        : '../../api/maintenance/schedule/create.php';
    
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
            closeScheduleModal();
            loadSchedules();
            loadSummaryStats();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error saving schedule:', error);
        showError('Error saving schedule');
    }
}

// View schedule
async function viewSchedule(id) {
    currentViewId = id;
    try {
        const response = await fetch(`../../api/maintenance/schedule/get.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            displayScheduleDetails(result.data);
            document.getElementById('viewScheduleModal').classList.add('active');
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error loading schedule:', error);
        showError('Error loading schedule details');
    }
}

// Display schedule details
function displayScheduleDetails(schedule) {
    const content = document.getElementById('scheduleDetailsContent');
    const checklistItems = schedule.checklist_items ? schedule.checklist_items.split('\n').filter(item => item.trim()) : [];
    
    content.innerHTML = `
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-label">Task Name:</div>
                <div class="detail-value"><strong>${escapeHtml(schedule.task_name)}</strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Machine:</div>
                <div class="detail-value">${escapeHtml(schedule.machine_name)} (${escapeHtml(schedule.machine_code)})</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Frequency:</div>
                <div class="detail-value">${getFrequencyBadge(schedule.frequency)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">${getStatusBadge(schedule.status)}</div>
            </div>
            <div class="detail-row full-width">
                <div class="detail-label">Task Description:</div>
                <div class="detail-value">${escapeHtml(schedule.task_description)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Estimated Duration:</div>
                <div class="detail-value">${schedule.estimated_duration} hours</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Assigned To:</div>
                <div class="detail-value">${escapeHtml(schedule.assigned_to_name || 'Unassigned')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Last Performed:</div>
                <div class="detail-value">${schedule.last_performed_date ? formatDate(schedule.last_performed_date) : 'Never'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Next Due Date:</div>
                <div class="detail-value">${formatDate(schedule.next_due_date)}</div>
            </div>
            ${checklistItems.length > 0 ? `
                <div class="detail-row full-width">
                    <div class="detail-label">Checklist Items:</div>
                    <div class="detail-value">
                        <ul>
                            ${checklistItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            ` : ''}
            <div class="detail-row full-width">
                <div class="detail-label">Notes:</div>
                <div class="detail-value">${escapeHtml(schedule.notes || 'N/A')}</div>
            </div>
        </div>
    `;
}

// Mark as performed
async function markAsPerformed() {
    if (!currentViewId) return;
    
    if (!confirm('Mark this maintenance task as performed today?')) {
        return;
    }
    
    try {
        const response = await fetch('../../api/maintenance/schedule/mark_performed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ schedule_id: currentViewId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeViewModal();
            loadSchedules();
            loadSummaryStats();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error marking as performed:', error);
        showError('Error updating schedule');
    }
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewScheduleModal').classList.remove('active');
    currentViewId = null;
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
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
