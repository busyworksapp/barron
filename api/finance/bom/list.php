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

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $sql = "SELECT b.*, p.product_name,
            (SELECT COUNT(*) FROM bom_components WHERE bom_id = b.id) as component_count
            FROM bom b
            INNER JOIN products p ON b.product_id = p.id
            WHERE 1=1";
    
    $params = [];
    
    // Search filter
    if (!empty($_GET['search'])) {
        $sql .= " AND (b.bom_number LIKE :search 
                  OR p.product_name LIKE :search 
                  OR b.version LIKE :search
                  OR b.description LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    
    // Status filter
    if (!empty($_GET['status'])) {
        $sql .= " AND b.status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    // Product filter
    if (!empty($_GET['product_id'])) {
        $sql .= " AND b.product_id = :product_id";
        $params[':product_id'] = $_GET['product_id'];
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $boms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $boms]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
