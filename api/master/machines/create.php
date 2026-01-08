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
$required = ['machine_code', 'machine_name', 'department_id', 'status'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        exit;
    }
}

// Validate status
$validStatuses = ['available', 'in_use', 'maintenance', 'down'];
if (!in_array($data['status'], $validStatuses)) {
    echo jsonResponse(false, 'Invalid status');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check for duplicate machine code
    $stmt = $db->prepare("SELECT id FROM machines WHERE machine_code = ?");
    $stmt->execute([$data['machine_code']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Machine code already exists');
        exit;
    }
    
    // Insert machine
    $stmt = $db->prepare("
        INSERT INTO machines (
            machine_code, machine_name, machine_number, description,
            department_id, status, last_maintenance_date, next_maintenance_date,
            maintenance_interval_days, purchase_date, manufacturer, model,
            serial_number, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['machine_code'],
        $data['machine_name'],
        $data['machine_number'] ?? null,
        $data['description'] ?? null,
        $data['department_id'],
        $data['status'],
        $data['last_maintenance_date'] ?? null,
        $data['next_maintenance_date'] ?? null,
        $data['maintenance_interval_days'] ?? null,
        $data['purchase_date'] ?? null,
        $data['manufacturer'] ?? null,
        $data['model'] ?? null,
        $data['serial_number'] ?? null,
        $data['notes'] ?? null,
        getCurrentUser()['id']
    ]);
    
    $machineId = $db->lastInsertId();
    
    // Log activity
    logActivity('machine_created', 'machines', $machineId, 
        "Created machine: {$data['machine_name']} ({$data['machine_code']})");
    
    echo jsonResponse(true, 'Machine created successfully', ['id' => $machineId]);
    
} catch (Exception $e) {
    error_log('Error in machines/create.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error creating machine: ' . $e->getMessage());
}
