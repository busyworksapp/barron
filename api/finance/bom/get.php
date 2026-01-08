<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('finance.view_bom')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'BOM ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get BOM details
    $stmt = $pdo->prepare("
        SELECT b.*, p.product_name, p.product_code
        FROM bom b
        INNER JOIN products p ON b.product_id = p.id
        WHERE b.id = :id
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $bom = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bom) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'BOM not found']);
        exit;
    }
    
    // Get BOM components
    $stmt = $pdo->prepare("
        SELECT * FROM bom_components
        WHERE bom_id = :bom_id
        ORDER BY id ASC
    ");
    $stmt->execute([':bom_id' => $_GET['id']]);
    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $bom['components'] = $components;
    
    echo json_encode(['success' => true, 'data' => $bom]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
