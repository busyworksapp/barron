<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('finance.edit_bom')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate required fields
$required = ['bom_id', 'bom_number', 'product_id', 'version', 'status', 'components', 'total_cost'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

// Validate status
$validStatuses = ['draft', 'active', 'obsolete'];
if (!in_array($_POST['status'], $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Check if BOM exists
    $stmt = $pdo->prepare("SELECT id FROM bom WHERE id = :id");
    $stmt->execute([':id' => $_POST['bom_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('BOM not found');
    }
    
    // Check for duplicate BOM number (excluding current BOM)
    $stmt = $pdo->prepare("SELECT id FROM bom WHERE bom_number = :bom_number AND id != :id");
    $stmt->execute([
        ':bom_number' => $_POST['bom_number'],
        ':id' => $_POST['bom_id']
    ]);
    if ($stmt->fetch()) {
        throw new Exception('BOM number already exists');
    }
    
    // Decode components JSON
    $components = json_decode($_POST['components'], true);
    if (!$components || count($components) === 0) {
        throw new Exception('At least one component is required');
    }
    
    // Update BOM
    $stmt = $pdo->prepare("
        UPDATE bom SET
            bom_number = :bom_number,
            product_id = :product_id,
            version = :version,
            status = :status,
            description = :description,
            overhead_percentage = :overhead_percentage,
            total_cost = :total_cost,
            notes = :notes,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':bom_number' => $_POST['bom_number'],
        ':product_id' => $_POST['product_id'],
        ':version' => $_POST['version'],
        ':status' => $_POST['status'],
        ':description' => $_POST['description'] ?? null,
        ':overhead_percentage' => $_POST['overhead_percentage'] ?? 0,
        ':total_cost' => $_POST['total_cost'],
        ':notes' => $_POST['notes'] ?? null,
        ':id' => $_POST['bom_id']
    ]);
    
    // Delete existing components
    $stmt = $pdo->prepare("DELETE FROM bom_components WHERE bom_id = :bom_id");
    $stmt->execute([':bom_id' => $_POST['bom_id']]);
    
    // Insert new components
    $componentStmt = $pdo->prepare("
        INSERT INTO bom_components (
            bom_id, component_name, quantity, unit, unit_cost, total_cost
        ) VALUES (
            :bom_id, :component_name, :quantity, :unit, :unit_cost, :total_cost
        )
    ");
    
    foreach ($components as $component) {
        $componentStmt->execute([
            ':bom_id' => $_POST['bom_id'],
            ':component_name' => $component['component_name'],
            ':quantity' => $component['quantity'],
            ':unit' => $component['unit'],
            ':unit_cost' => $component['unit_cost'],
            ':total_cost' => $component['total_cost']
        ]);
    }
    
    // Log activity
    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details)
        VALUES (:user_id, :action, :details)
    ");
    $logStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':action' => 'update_bom',
        ':details' => "Updated BOM {$_POST['bom_number']} with " . count($components) . " components"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'BOM updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
