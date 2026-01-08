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
$required = ['machine_id', 'machine_code', 'machine_name', 'department_id', 'status'];
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
    
    // Check if machine exists
    $stmt = $db->prepare("SELECT * FROM machines WHERE id = ?");
    $stmt->execute([$data['machine_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo jsonResponse(false, 'Machine not found');
        exit;
    }
    
    // Check for duplicate machine code (excluding current machine)
    $stmt = $db->prepare("SELECT id FROM machines WHERE machine_code = ? AND id != ?");
    $stmt->execute([$data['machine_code'], $data['machine_id']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Machine code already exists');
        exit;
    }
    
    // Update machine
    $stmt = $db->prepare("
        UPDATE machines SET 
            machine_code = ?, 
            machine_name = ?, 
            machine_number = ?, 
            description = ?,
            department_id = ?, 
            status = ?, 
            last_maintenance_date = ?, 
            next_maintenance_date = ?,
            maintenance_interval_days = ?, 
            purchase_date = ?, 
            manufacturer = ?, 
            model = ?,
            serial_number = ?, 
            notes = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
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
        $data['machine_id']
    ]);
    
    // Build change log
    $changes = [];
    foreach ($existing as $key => $value) {
        if (isset($data[$key]) && $data[$key] != $value && $key != 'updated_at') {
            $changes[] = "$key: '$value' → '{$data[$key]}'";
        }
    }
    
    // Log activity
    if (!empty($changes)) {
        logActivity('machine_updated', 'machines', $data['machine_id'], 
            "Updated machine: {$data['machine_name']} - " . implode(', ', $changes));
    }
    
    echo jsonResponse(true, 'Machine updated successfully');
    
} catch (Exception $e) {
    error_log('Error in machines/update.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error updating machine: ' . $e->getMessage());
}
