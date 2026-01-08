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
$required = ['employee_number', 'first_name', 'last_name', 'username', 'primary_department_id', 'role_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        exit;
    }
}

// Validate password for new employee
if (!isset($data['password']) || empty($data['password'])) {
    echo jsonResponse(false, 'Password is required for new employees');
    exit;
}

if (strlen($data['password']) < 6) {
    echo jsonResponse(false, 'Password must be at least 6 characters');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check for duplicate employee number
    $stmt = $db->prepare("SELECT id FROM employees WHERE employee_number = ?");
    $stmt->execute([$data['employee_number']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Employee number already exists');
        exit;
    }
    
    // Check for duplicate username
    $stmt = $db->prepare("SELECT id FROM employees WHERE username = ?");
    $stmt->execute([$data['username']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Username already exists');
        exit;
    }
    
    // Check for duplicate email if provided
    if (!empty($data['email'])) {
        $stmt = $db->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            echo jsonResponse(false, 'Email already exists');
            exit;
        }
    }
    
    $db->beginTransaction();
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
    
    // Insert employee
    $stmt = $db->prepare("
        INSERT INTO employees (
            employee_number, first_name, last_name, email, phone,
            username, password, primary_department_id, role_id, is_active, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['employee_number'],
        $data['first_name'],
        $data['last_name'],
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $data['username'],
        $hashedPassword,
        $data['primary_department_id'],
        $data['role_id'],
        $data['is_active'] ?? 1,
        getCurrentUser()['id']
    ]);
    
    $employeeId = $db->lastInsertId();
    
    // Insert additional departments
    if (!empty($data['additional_departments'])) {
        $stmt = $db->prepare("INSERT INTO employee_departments (employee_id, department_id) VALUES (?, ?)");
        foreach ($data['additional_departments'] as $deptId) {
            $stmt->execute([$employeeId, $deptId]);
        }
    }
    
    // Log activity
    logActivity('employee_created', 'employees', $employeeId, 
        "Created employee: {$data['first_name']} {$data['last_name']} ({$data['employee_number']})");
    
    $db->commit();
    
    echo jsonResponse(true, 'Employee created successfully', ['id' => $employeeId]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in employees/create.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error creating employee: ' . $e->getMessage());
}
