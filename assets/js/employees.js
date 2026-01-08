// Employees Management JavaScript
let departments = [];
let roles = [];
let currentEmployeeId = null;

// Load employees on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDepartments();
    loadRoles();
    loadEmployees();
    
    // Auto-generate username when first name changes
    document.getElementById('first_name').addEventListener('input', generateUsername);
    
    // Setup form submission
    document.getElementById('employeeForm').addEventListener('submit', saveEmployee);
    document.getElementById('departmentsForm').addEventListener('submit', saveDepartments);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadEmployees();
        }
    });
});

// Load departments for filters and form
function loadDepartments() {
    fetch('../../api/master/departments/list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                departments = data.data;
                populateDepartmentFilters();
                populateDepartmentSelect();
            }
        })
        .catch(error => console.error('Error loading departments:', error));
}

// Load roles for filters and form
function loadRoles() {
    fetch('../../api/master/employees/roles.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                roles = data.data;
                populateRoleFilters();
                populateRoleSelect();
            }
        })
        .catch(error => console.error('Error loading roles:', error));
}

// Populate department filter and select
function populateDepartmentFilters() {
    const filter = document.getElementById('departmentFilter');
    const select = document.getElementById('primary_department_id');
    
    filter.innerHTML = '<option value="">All Departments</option>';
    select.innerHTML = '<option value="">Select Department</option>';
    
    departments.forEach(dept => {
        if (dept.is_active == 1) {
            filter.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
            select.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
        }
    });
}

// Populate role filter and select
function populateRoleFilters() {
    const filter = document.getElementById('roleFilter');
    const select = document.getElementById('role_id');
    
    filter.innerHTML = '<option value="">All Roles</option>';
    select.innerHTML = '<option value="">Select Role</option>';
    
    roles.forEach(role => {
        filter.innerHTML += `<option value="${role.id}">${escapeHtml(role.role_name)}</option>`;
        select.innerHTML += `<option value="${role.id}">${escapeHtml(role.role_name)}</option>`;
    });
}

// Populate department select in form
function populateDepartmentSelect() {
    const select = document.getElementById('primary_department_id');
    select.innerHTML = '<option value="">Select Department</option>';
    
    departments.forEach(dept => {
        if (dept.is_active == 1) {
            select.innerHTML += `<option value="${dept.id}">${escapeHtml(dept.department_name)}</option>`;
        }
    });
}

// Populate role select in form
function populateRoleSelect() {
    const select = document.getElementById('role_id');
    select.innerHTML = '<option value="">Select Role</option>';
    
    roles.forEach(role => {
        select.innerHTML += `<option value="${role.id}">${escapeHtml(role.role_name)}</option>`;
    });
}

// Load employees with filters
function loadEmployees() {
    const search = document.getElementById('searchInput').value;
    const department = document.getElementById('departmentFilter').value;
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = '../../api/master/employees/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (department) url += `department_id=${department}&`;
    if (role) url += `role_id=${role}&`;
    if (status !== '') url += `is_active=${status}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEmployees(data.data);
            } else {
                showAlert('Error loading employees: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading employees', 'error');
        });
}

// Display employees in table
function displayEmployees(employees) {
    const tbody = document.getElementById('employeesTableBody');
    
    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No employees found</td></tr>';
        return;
    }
    
    let html = '';
    employees.forEach(emp => {
        const statusBadge = emp.is_active == 1 
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-danger">Inactive</span>';
        
        html += `
            <tr>
                <td>${escapeHtml(emp.employee_number)}</td>
                <td>${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</td>
                <td>${escapeHtml(emp.username)}</td>
                <td>${emp.email ? escapeHtml(emp.email) : '-'}</td>
                <td>${emp.phone ? escapeHtml(emp.phone) : '-'}</td>
                <td>${emp.department_name ? escapeHtml(emp.department_name) : '-'}</td>
                <td>${escapeHtml(emp.role_name)}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-edit" onclick="editEmployee(${emp.id})" title="Edit">
                        <span class="icon">✎</span>
                    </button>
                    <button class="btn-action btn-secondary" onclick="manageDepartments(${emp.id}, '${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}')" title="Manage Departments">
                        <span class="icon">⚙</span>
                    </button>
                    <button class="btn-action btn-warning" onclick="resetPassword(${emp.id})" title="Reset Password">
                        <span class="icon">🔑</span>
                    </button>
                    <button class="btn-action btn-danger" onclick="deleteEmployee(${emp.id}, '${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}')" title="Delete">
                        <span class="icon">🗑</span>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Open employee modal for adding
function openEmployeeModal() {
    currentEmployeeId = null;
    document.getElementById('modalTitle').textContent = 'Add Employee';
    document.getElementById('employeeForm').reset();
    document.getElementById('employee_id').value = '';
    document.getElementById('username').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('password').required = true;
    document.getElementById('passwordSection').querySelector('.form-text').textContent = 'Required for new employees. Operators use employee number.';
    
    // Load additional departments checkboxes
    loadAdditionalDepartments([]);
    
    document.getElementById('employeeModal').classList.add('active');
}

// Close employee modal
function closeEmployeeModal() {
    document.getElementById('employeeModal').classList.remove('active');
}

// Generate username from first name
function generateUsername() {
    const firstName = document.getElementById('first_name').value.trim().toLowerCase();
    if (firstName) {
        document.getElementById('username').value = firstName + '@barron';
    } else {
        document.getElementById('username').value = '';
    }
}

// Load additional departments checkboxes
function loadAdditionalDepartments(selectedDepts = []) {
    const container = document.getElementById('additionalDepartments');
    const primaryDeptId = document.getElementById('primary_department_id').value;
    
    let html = '';
    departments.forEach(dept => {
        if (dept.is_active == 1 && dept.id != primaryDeptId) {
            const checked = selectedDepts.includes(dept.id) ? 'checked' : '';
            html += `
                <label class="checkbox-label">
                    <input type="checkbox" name="additional_departments[]" value="${dept.id}" ${checked}>
                    <span>${escapeHtml(dept.department_name)}</span>
                </label>
            `;
        }
    });
    
    container.innerHTML = html || '<p class="text-muted">No additional departments available</p>';
}

// Update additional departments when primary department changes
document.addEventListener('DOMContentLoaded', function() {
    const primaryDept = document.getElementById('primary_department_id');
    if (primaryDept) {
        primaryDept.addEventListener('change', function() {
            const currentlySelected = Array.from(document.querySelectorAll('input[name="additional_departments[]"]:checked'))
                .map(cb => parseInt(cb.value));
            loadAdditionalDepartments(currentlySelected);
        });
    }
});

// Save employee
function saveEmployee(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const employeeId = document.getElementById('employee_id').value;
    
    // Get additional departments
    const additionalDepts = Array.from(document.querySelectorAll('input[name="additional_departments[]"]:checked'))
        .map(cb => parseInt(cb.value));
    
    const data = {
        employee_number: formData.get('employee_number'),
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        username: formData.get('username'),
        password: formData.get('password'),
        primary_department_id: formData.get('primary_department_id'),
        role_id: formData.get('role_id'),
        is_active: document.getElementById('is_active').checked ? 1 : 0,
        additional_departments: additionalDepts
    };
    
    if (employeeId) {
        data.employee_id = employeeId;
    }
    
    const url = employeeId 
        ? '../../api/master/employees/update.php'
        : '../../api/master/employees/create.php';
    
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
            showAlert(employeeId ? 'Employee updated successfully' : 'Employee created successfully', 'success');
            closeEmployeeModal();
            loadEmployees();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving employee', 'error');
    });
}

// Edit employee
function editEmployee(id) {
    fetch(`../../api/master/employees/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const emp = data.data;
                currentEmployeeId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Employee';
                document.getElementById('employee_id').value = emp.id;
                document.getElementById('employee_number').value = emp.employee_number;
                document.getElementById('first_name').value = emp.first_name;
                document.getElementById('last_name').value = emp.last_name;
                document.getElementById('email').value = emp.email || '';
                document.getElementById('phone').value = emp.phone || '';
                document.getElementById('username').value = emp.username;
                document.getElementById('primary_department_id').value = emp.primary_department_id || '';
                document.getElementById('role_id').value = emp.role_id;
                document.getElementById('is_active').checked = emp.is_active == 1;
                document.getElementById('password').value = '';
                document.getElementById('password').required = false;
                document.getElementById('passwordSection').querySelector('.form-text').textContent = 'Leave blank to keep existing password';
                
                // Load additional departments with selected ones
                loadAdditionalDepartments(emp.additional_departments || []);
                
                document.getElementById('employeeModal').classList.add('active');
            } else {
                showAlert('Error loading employee: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading employee', 'error');
        });
}

// Delete employee
function deleteEmployee(id, name) {
    if (!confirm(`Are you sure you want to delete employee "${name}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch('../../api/master/employees/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ employee_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Employee deleted successfully', 'success');
            loadEmployees();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting employee', 'error');
    });
}

// Manage departments for employee
function manageDepartments(id, name) {
    currentEmployeeId = id;
    document.getElementById('deptEmployeeName').textContent = name;
    document.getElementById('dept_employee_id').value = id;
    
    // Load current departments
    fetch(`../../api/master/employees/departments.php?employee_id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selectedDepts = data.data.map(d => d.department_id);
                loadDepartmentsList(selectedDepts);
                document.getElementById('departmentsModal').classList.add('active');
            } else {
                showAlert('Error loading departments: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading departments', 'error');
        });
}

// Load departments list for modal
function loadDepartmentsList(selectedDepts) {
    const container = document.getElementById('departmentsList');
    
    let html = '';
    departments.forEach(dept => {
        if (dept.is_active == 1) {
            const checked = selectedDepts.includes(dept.id) ? 'checked' : '';
            html += `
                <label class="checkbox-label">
                    <input type="checkbox" name="departments[]" value="${dept.id}" ${checked}>
                    <span>${escapeHtml(dept.department_name)}</span>
                </label>
            `;
        }
    });
    
    container.innerHTML = html;
}

// Close departments modal
function closeDepartmentsModal() {
    document.getElementById('departmentsModal').classList.remove('active');
}

// Save departments
function saveDepartments(e) {
    e.preventDefault();
    
    const employeeId = document.getElementById('dept_employee_id').value;
    const selectedDepts = Array.from(document.querySelectorAll('input[name="departments[]"]:checked'))
        .map(cb => parseInt(cb.value));
    
    fetch('../../api/master/employees/save_departments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            employee_id: employeeId,
            departments: selectedDepts
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Departments updated successfully', 'success');
            closeDepartmentsModal();
            loadEmployees();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving departments', 'error');
    });
}

// Reset password
function resetPassword(id) {
    const newPassword = prompt('Enter new password (minimum 6 characters):');
    
    if (!newPassword) {
        return;
    }
    
    if (newPassword.length < 6) {
        showAlert('Password must be at least 6 characters', 'error');
        return;
    }
    
    fetch('../../api/master/employees/reset_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            employee_id: id,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Password reset successfully', 'success');
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error resetting password', 'error');
    });
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
