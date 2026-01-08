<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.edit')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['employee_id', 'employee_number', 'first_name', 'last_name', 'username', 'primary_department_id', 'role_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        exit;
    }
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if employee exists
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$data['employee_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo jsonResponse(false, 'Employee not found');
        exit;
    }
    
    // Check for duplicate employee number (excluding current employee)
    $stmt = $db->prepare("SELECT id FROM employees WHERE employee_number = ? AND id != ?");
    $stmt->execute([$data['employee_number'], $data['employee_id']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Employee number already exists');
        exit;
    }
    
    // Check for duplicate username (excluding current employee)
    $stmt = $db->prepare("SELECT id FROM employees WHERE username = ? AND id != ?");
    $stmt->execute([$data['username'], $data['employee_id']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Username already exists');
        exit;
    }
    
    // Check for duplicate email if provided (excluding current employee)
    if (!empty($data['email'])) {
        $stmt = $db->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $stmt->execute([$data['email'], $data['employee_id']]);
        if ($stmt->fetch()) {
            echo jsonResponse(false, 'Email already exists');
            exit;
        }
    }
    
    $db->beginTransaction();
    
    // Prepare update SQL
    $sql = "UPDATE employees SET 
            employee_number = ?, 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?,
            username = ?, 
            primary_department_id = ?, 
            role_id = ?, 
            is_active = ?,
            updated_at = CURRENT_TIMESTAMP";
    
    $params = [
        $data['employee_number'],
        $data['first_name'],
        $data['last_name'],
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $data['username'],
        $data['primary_department_id'],
        $data['role_id'],
        $data['is_active'] ?? 1
    ];
    
    // Update password if provided
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            echo jsonResponse(false, 'Password must be at least 6 characters');
            exit;
        }
        $sql .= ", password = ?";
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $data['employee_id'];
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Update additional departments
    // First, delete existing assignments
    $stmt = $db->prepare("DELETE FROM employee_departments WHERE employee_id = ?");
    $stmt->execute([$data['employee_id']]);
    
    // Then insert new ones
    if (!empty($data['additional_departments'])) {
        $stmt = $db->prepare("INSERT INTO employee_departments (employee_id, department_id) VALUES (?, ?)");
        foreach ($data['additional_departments'] as $deptId) {
            $stmt->execute([$data['employee_id'], $deptId]);
        }
    }
    
    // Build change log
    $changes = [];
    foreach ($existing as $key => $value) {
        if (isset($data[$key]) && $data[$key] != $value && $key != 'password' && $key != 'updated_at') {
            $changes[] = "$key: '$value' → '{$data[$key]}'";
        }
    }
    
    if (!empty($data['password'])) {
        $changes[] = "password updated";
    }
    
    // Log activity
    if (!empty($changes)) {
        logActivity('employee_updated', 'employees', $data['employee_id'], 
            "Updated employee: {$data['first_name']} {$data['last_name']} - " . implode(', ', $changes));
    }
    
    $db->commit();
    
    echo jsonResponse(true, 'Employee updated successfully');
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in employees/update.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error updating employee: ' . $e->getMessage());
}
