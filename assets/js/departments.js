/**
 * Departments Management JavaScript
 */

let currentDepartmentId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDepartments();
    
    // Search on enter key
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadDepartments();
        }
    });
});

/**
 * Load departments list
 */
async function loadDepartments() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status !== '') params.append('is_active', status);
    
    try {
        const response = await fetch(`../../api/master/departments/list.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            renderDepartmentsTable(data.data);
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading departments:', error);
        showAlert('Failed to load departments', 'danger');
    }
}

/**
 * Render departments table
 */
function renderDepartmentsTable(departments) {
    const tbody = document.getElementById('departmentsTable');
    
    if (departments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <p>No departments found</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = departments.map(dept => `
        <tr>
            <td><strong>${escapeHtml(dept.department_code)}</strong></td>
            <td>${escapeHtml(dept.department_name)}</td>
            <td>${formatNumber(dept.daily_target)}</td>
            <td>${formatNumber(dept.weekly_target)}</td>
            <td>${formatNumber(dept.monthly_target)}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="openStagesModal(${dept.id}, '${escapeHtml(dept.department_name)}')">
                    ${dept.stages_count || 0} Stages
                </button>
            </td>
            <td>
                <span class="badge badge-${dept.is_active == 1 ? 'success' : 'secondary'}">
                    ${dept.is_active == 1 ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="actions-cell">
                <button class="btn-action btn-edit" onclick="editDepartment(${dept.id})" title="Edit">
                    ✏️
                </button>
                <button class="btn-action btn-delete" onclick="deleteDepartment(${dept.id})" title="Delete">
                    🗑️
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Open department modal for adding
 */
function openDepartmentModal() {
    document.getElementById('modalTitle').textContent = 'Add Department';
    document.getElementById('departmentForm').reset();
    document.getElementById('departmentId').value = '';
    document.getElementById('isActive').checked = true;
    document.getElementById('departmentModal').classList.add('open');
}

/**
 * Close department modal
 */
function closeDepartmentModal() {
    document.getElementById('departmentModal').classList.remove('open');
}

/**
 * Edit department
 */
async function editDepartment(id) {
    try {
        const response = await fetch(`../../api/master/departments/get.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const dept = data.data;
            document.getElementById('modalTitle').textContent = 'Edit Department';
            document.getElementById('departmentId').value = dept.id;
            document.getElementById('departmentCode').value = dept.department_code;
            document.getElementById('departmentName').value = dept.department_name;
            document.getElementById('dailyTarget').value = dept.daily_target;
            document.getElementById('weeklyTarget').value = dept.weekly_target;
            document.getElementById('monthlyTarget').value = dept.monthly_target;
            document.getElementById('isActive').checked = dept.is_active == 1;
            
            document.getElementById('departmentModal').classList.add('open');
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading department:', error);
        showAlert('Failed to load department', 'danger');
    }
}

/**
 * Save department
 */
async function saveDepartment() {
    const form = document.getElementById('departmentForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = {
        id: document.getElementById('departmentId').value,
        department_code: document.getElementById('departmentCode').value,
        department_name: document.getElementById('departmentName').value,
        daily_target: document.getElementById('dailyTarget').value || 0,
        weekly_target: document.getElementById('weeklyTarget').value || 0,
        monthly_target: document.getElementById('monthlyTarget').value || 0,
        is_active: document.getElementById('isActive').checked ? 1 : 0
    };
    
    try {
        const url = formData.id ? '../../api/master/departments/update.php' : '../../api/master/departments/create.php';
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            closeDepartmentModal();
            loadDepartments();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving department:', error);
        showAlert('Failed to save department', 'danger');
    }
}

/**
 * Delete department
 */
async function deleteDepartment(id) {
    if (!confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('../../api/master/departments/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            loadDepartments();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting department:', error);
        showAlert('Failed to delete department', 'danger');
    }
}

/**
 * Open stages modal
 */
async function openStagesModal(departmentId, departmentName) {
    document.getElementById('stagesModalTitle').textContent = `Production Stages - ${departmentName}`;
    document.getElementById('stagesDepartmentId').value = departmentId;
    
    try {
        const response = await fetch(`../../api/master/departments/stages.php?department_id=${departmentId}`);
        const data = await response.json();
        
        if (data.success) {
            renderStagesTable(data.data);
            document.getElementById('stagesModal').classList.add('open');
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading stages:', error);
        showAlert('Failed to load stages', 'danger');
    }
}

/**
 * Close stages modal
 */
function closeStagesModal() {
    document.getElementById('stagesModal').classList.remove('open');
}

/**
 * Render stages table
 */
function renderStagesTable(stages) {
    const tbody = document.getElementById('stagesTableBody');
    
    if (stages.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    <p class="text-muted">No stages defined. Click "Add Stage" to create one.</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = stages.map(stage => `
        <tr class="stage-row" data-stage-id="${stage.id || ''}">
            <td>
                <input type="number" class="form-control" name="stage_order" value="${stage.stage_order}" min="1" required>
            </td>
            <td>
                <input type="text" class="form-control stage-code" name="stage_code" value="${escapeHtml(stage.stage_code || '')}" maxlength="50" required>
            </td>
            <td>
                <input type="text" class="form-control" name="stage_name" value="${escapeHtml(stage.stage_name || '')}" maxlength="255" required>
            </td>
            <td>
                <input type="number" class="form-control" name="estimated_duration_hours" value="${stage.estimated_duration_hours || ''}" step="0.5" min="0">
            </td>
            <td class="text-center">
                <input type="checkbox" name="is_active" ${stage.is_active == 1 ? 'checked' : ''}>
            </td>
            <td>
                <button class="btn-action btn-delete" onclick="removeStageRow(this)" title="Remove">
                    ❌
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Add new stage row
 */
function addStageRow() {
    const tbody = document.getElementById('stagesTableBody');
    const rowCount = tbody.querySelectorAll('tr').length;
    
    // Remove empty state if exists
    if (tbody.querySelector('.text-muted')) {
        tbody.innerHTML = '';
    }
    
    const newRow = document.createElement('tr');
    newRow.className = 'stage-row';
    newRow.innerHTML = `
        <td>
            <input type="number" class="form-control" name="stage_order" value="${rowCount + 1}" min="1" required>
        </td>
        <td>
            <input type="text" class="form-control stage-code" name="stage_code" maxlength="50" required>
        </td>
        <td>
            <input type="text" class="form-control" name="stage_name" maxlength="255" required>
        </td>
        <td>
            <input type="number" class="form-control" name="estimated_duration_hours" step="0.5" min="0">
        </td>
        <td class="text-center">
            <input type="checkbox" name="is_active" checked>
        </td>
        <td>
            <button class="btn-action btn-delete" onclick="removeStageRow(this)" title="Remove">
                ❌
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
}

/**
 * Remove stage row
 */
function removeStageRow(button) {
    const row = button.closest('tr');
    row.remove();
    
    // Show empty state if no rows left
    const tbody = document.getElementById('stagesTableBody');
    if (tbody.querySelectorAll('tr').length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    <p class="text-muted">No stages defined. Click "Add Stage" to create one.</p>
                </td>
            </tr>
        `;
    }
}

/**
 * Save all stages
 */
async function saveStages() {
    const departmentId = document.getElementById('stagesDepartmentId').value;
    const tbody = document.getElementById('stagesTableBody');
    const rows = tbody.querySelectorAll('tr.stage-row');
    
    const stages = [];
    let isValid = true;
    
    rows.forEach(row => {
        const stageId = row.dataset.stageId || null;
        const stageOrder = row.querySelector('input[name="stage_order"]').value;
        const stageCode = row.querySelector('input[name="stage_code"]').value;
        const stageName = row.querySelector('input[name="stage_name"]').value;
        const duration = row.querySelector('input[name="estimated_duration_hours"]').value;
        const isActive = row.querySelector('input[name="is_active"]').checked;
        
        if (!stageCode || !stageName || !stageOrder) {
            isValid = false;
            return;
        }
        
        stages.push({
            id: stageId,
            stage_code: stageCode,
            stage_name: stageName,
            stage_order: parseInt(stageOrder),
            estimated_duration_hours: duration ? parseFloat(duration) : null,
            is_active: isActive ? 1 : 0
        });
    });
    
    if (!isValid) {
        showAlert('Please fill in all required fields', 'danger');
        return;
    }
    
    try {
        const response = await fetch('../../api/master/departments/save_stages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                department_id: departmentId,
                stages: stages
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            closeStagesModal();
            loadDepartments();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving stages:', error);
        showAlert('Failed to save stages', 'danger');
    }
}

/**
 * Reset filters
 */
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '1';
    loadDepartments();
}

/**
 * Show alert message
 */
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Format number
 */
function formatNumber(value) {
    return value ? parseFloat(value).toLocaleString() : '0';
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
