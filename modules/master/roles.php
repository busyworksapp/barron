<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('admin')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Roles Management';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Barron Production System</title>
    <link rel="stylesheet" href="../../assets/css/industrial.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/master.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="page-actions">
                    <button type="button" class="btn btn-primary" onclick="openRoleModal()">
                        <span class="icon">+</span> Add Role
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>System Roles</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Permissions</th>
                                    <th>Users</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rolesTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">Add Role</h2>
                <span class="close" onclick="closeRoleModal()">&times;</span>
            </div>
            <form id="roleForm">
                <input type="hidden" id="roleId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role_name">Role Name *</label>
                        <input type="text" id="role_name" name="role_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role_code">Role Code *</label>
                        <input type="text" id="role_code" name="role_code" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Permissions</label>
                    <div id="permissionsContainer" class="permissions-grid">
                        <div class="permission-group">
                            <h4>Master Data</h4>
                            <label><input type="checkbox" name="permissions[]" value="master.view"> View</label>
                            <label><input type="checkbox" name="permissions[]" value="master.edit"> Edit</label>
                        </div>
                        <div class="permission-group">
                            <h4>Planning</h4>
                            <label><input type="checkbox" name="permissions[]" value="planning.view"> View</label>
                            <label><input type="checkbox" name="permissions[]" value="planning.edit"> Edit</label>
                        </div>
                        <div class="permission-group">
                            <h4>Defects</h4>
                            <label><input type="checkbox" name="permissions[]" value="defects.view"> View</label>
                            <label><input type="checkbox" name="permissions[]" value="defects.edit"> Edit</label>
                        </div>
                        <div class="permission-group">
                            <h4>Maintenance</h4>
                            <label><input type="checkbox" name="permissions[]" value="maintenance.view"> View</label>
                            <label><input type="checkbox" name="permissions[]" value="maintenance.edit"> Edit</label>
                        </div>
                        <div class="permission-group">
                            <h4>SOP</h4>
                            <label><input type="checkbox" name="permissions[]" value="sop.view"> View</label>
                            <label><input type="checkbox" name="permissions[]" value="sop.edit"> Edit</label>
                        </div>
                        <div class="permission-group">
                            <h4>Finance</h4>
                            <label><input type="checkbox" name="permissions[]" value="finance.view"> View</label>
                            <label><input type="checkbox" name="permissions[]" value="finance.edit"> Edit</label>
                        </div>
                        <div class="permission-group">
                            <h4>Administration</h4>
                            <label><input type="checkbox" name="permissions[]" value="admin"> Full Admin Access</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/roles.js"></script>
</body>
</html>
