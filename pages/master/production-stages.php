<?php
/**
 * Barron Production Management System
 * Production Stages Management Page
 */

require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../helpers/functions.php';

// Check authentication
session_start();
$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Check permissions
if (!hasPermission('master.view')) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

$can_edit = hasPermission('master.edit');
$page_title = 'Production Stages Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Barron</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stages-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .dept-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .dept-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }
        .dept-name { font-size: 18px; font-weight: bold; color: #333; }
        .stage-count { font-size: 14px; color: #666; }
        .stage-list { list-style: none; padding: 0; margin: 0; }
        .stage-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; margin: 5px 0; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; cursor: move; }
        .stage-item.inactive { opacity: 0.5; border-left-color: #ccc; }
        .stage-info { flex: 1; }
        .stage-name { font-weight: 500; color: #333; }
        .stage-code { font-size: 12px; color: #666; }
        .stage-actions { display: flex; gap: 5px; }
        .btn-icon { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-edit { background: #ffc107; color: white; }
        .btn-toggle { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-add { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; width: 100%; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 50px auto; padding: 30px; width: 90%; max-width: 600px; border-radius: 8px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 24px; font-weight: bold; }
        .close { font-size: 28px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .btn-primary { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-secondary { background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        @media (max-width: 768px) {
            .dept-grid { grid-template-columns: 1fr; }
            .modal-content { margin: 20px; width: calc(100% - 40px); }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="stages-container">
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            <p>Manage production stages for each department</p>
        </div>

        <div id="departmentsGrid" class="dept-grid">
            <div class="empty-state">Loading departments...</div>
        </div>
    </div>

    <!-- Add/Edit Stage Modal -->
    <div id="stageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add Production Stage</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="stageForm">
                <input type="hidden" id="stageId" name="id">
                <input type="hidden" id="departmentId" name="department_id">
                
                <div class="form-group">
                    <label for="stageName">Stage Name *</label>
                    <input type="text" id="stageName" name="stage_name" required>
                </div>
                
                <div class="form-group">
                    <label for="stageCode">Stage Code *</label>
                    <input type="text" id="stageCode" name="stage_code" required placeholder="e.g., EMB_DESIGN">
                </div>
                
                <div class="form-group">
                    <label for="stageOrder">Order</label>
                    <input type="number" id="stageOrder" name="stage_order" min="1">
                </div>
                
                <div class="form-group">
                    <label for="estimatedHours">Estimated Hours</label>
                    <input type="number" id="estimatedHours" name="estimated_hours" step="0.5" min="0">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="isActive" name="is_active" checked>
                        Active
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Stage</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = '../api/master/production_stages.php';
        let currentDepartmentId = null;
        let currentStageId = null;

        // Load departments on page load
        document.addEventListener('DOMContentLoaded', loadDepartments);

        // Load all departments with their stages
        async function loadDepartments() {
            try {
                const response = await fetch(API_URL + '?action=departments');
                const result = await response.json();
                
                if (result.success) {
                    renderDepartments(result.data);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Error loading departments: ' + error.message);
            }
        }

        // Render departments grid
        function renderDepartments(departments) {
            const grid = document.getElementById('departmentsGrid');
            
            if (departments.length === 0) {
                grid.innerHTML = '<div class="empty-state">No departments found</div>';
                return;
            }
            
            grid.innerHTML = departments.map(dept => `
                <div class="dept-card" data-dept-id="${dept.department_id}">
                    <div class="dept-header">
                        <div>
                            <div class="dept-name">${dept.department_name}</div>
                            <div class="stage-count">${dept.active_count}/${dept.stage_count} stages active</div>
                        </div>
                        <?php if ($can_edit): ?>
                        <button class="btn-add" onclick="openAddModal(${dept.department_id}, '${dept.department_name}')">
                            + Add Stage
                        </button>
                        <?php endif; ?>
                    </div>
                    <div id="stages-${dept.department_id}" class="stage-list-container">
                        <div style="text-align: center; padding: 20px; color: #666;">Loading stages...</div>
                    </div>
                </div>
            `).join('');
            
            // Load stages for each department
            departments.forEach(dept => loadStages(dept.department_id));
        }

        // Load stages for a department
        async function loadStages(deptId) {
            try {
                const response = await fetch(API_URL + '?department_id=' + deptId);
                const result = await response.json();
                
                if (result.success) {
                    renderStages(deptId, result.data);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Error loading stages: ' + error.message);
            }
        }

        // Render stages list
        function renderStages(deptId, stages) {
            const container = document.getElementById('stages-' + deptId);
            
            if (stages.length === 0) {
                container.innerHTML = '<div class="empty-state">No stages defined yet</div>';
                return;
            }
            
            container.innerHTML = '<ul class="stage-list">' + stages.map(stage => `
                <li class="stage-item ${stage.is_active == 0 ? 'inactive' : ''}" data-stage-id="${stage.id}">
                    <div class="stage-info">
                        <div class="stage-name">${stage.stage_name}</div>
                        <div class="stage-code">${stage.stage_code} • Order: ${stage.stage_order}</div>
                    </div>
                    <?php if ($can_edit): ?>
                    <div class="stage-actions">
                        <button class="btn-icon btn-edit" onclick="openEditModal(${stage.id})" title="Edit">✎</button>
                        <button class="btn-icon btn-toggle" onclick="toggleStage(${stage.id})" title="Toggle Active">
                            ${stage.is_active == 1 ? '✓' : '○'}
                        </button>
                        <button class="btn-icon btn-delete" onclick="deleteStage(${stage.id}, ${deptId})" title="Delete">×</button>
                    </div>
                    <?php endif; ?>
                </li>
            `).join('') + '</ul>';
        }

        // Open add modal
        function openAddModal(deptId, deptName) {
            currentDepartmentId = deptId;
            currentStageId = null;
            
            document.getElementById('modalTitle').textContent = 'Add Stage to ' + deptName;
            document.getElementById('stageForm').reset();
            document.getElementById('stageId').value = '';
            document.getElementById('departmentId').value = deptId;
            document.getElementById('isActive').checked = true;
            
            document.getElementById('stageModal').style.display = 'block';
        }

        // Open edit modal
        async function openEditModal(stageId) {
            try {
                const response = await fetch(API_URL + '?id=' + stageId);
                const result = await response.json();
                
                if (result.success) {
                    const stage = result.data;
                    currentStageId = stageId;
                    currentDepartmentId = stage.department_id;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Stage';
                    document.getElementById('stageId').value = stage.id;
                    document.getElementById('departmentId').value = stage.department_id;
                    document.getElementById('stageName').value = stage.stage_name;
                    document.getElementById('stageCode').value = stage.stage_code;
                    document.getElementById('stageOrder').value = stage.stage_order;
                    document.getElementById('estimatedHours').value = stage.estimated_hours || '';
                    document.getElementById('description').value = stage.description || '';
                    document.getElementById('isActive').checked = stage.is_active == 1;
                    
                    document.getElementById('stageModal').style.display = 'block';
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Error loading stage: ' + error.message);
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('stageModal').style.display = 'none';
            document.getElementById('stageForm').reset();
            currentDepartmentId = null;
            currentStageId = null;
        }

        // Handle form submission
        document.getElementById('stageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'is_active') {
                    data[key] = document.getElementById('isActive').checked ? 1 : 0;
                } else if (value !== '') {
                    data[key] = value;
                }
            });
            
            try {
                const method = currentStageId ? 'PUT' : 'POST';
                const response = await fetch(API_URL, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    closeModal();
                    loadStages(currentDepartmentId);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Error saving stage: ' + error.message);
            }
        });

        // Toggle stage active status
        async function toggleStage(stageId) {
            if (!confirm('Toggle this stage\'s active status?')) return;
            
            try {
                const response = await fetch(API_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: stageId, action: 'toggle_active' })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    loadDepartments();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Error toggling stage: ' + error.message);
            }
        }

        // Delete stage
        async function deleteStage(stageId, deptId) {
            if (!confirm('Are you sure you want to delete this stage? This action cannot be undone.')) return;
            
            try {
                const response = await fetch(API_URL + '?id=' + stageId, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    loadStages(deptId);
                    loadDepartments(); // Refresh counts
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Error deleting stage: ' + error.message);
            }
        }

        // Show success message
        function showSuccess(message) {
            alert('✓ ' + message);
        }

        // Show error message
        function showError(message) {
            alert('✗ ' + message);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('stageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
