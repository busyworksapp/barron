<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.view')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Build query with filters
    $sql = "SELECT p.*, 
            CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
            FROM products p
            LEFT JOIN employees creator ON p.created_by = creator.id
            WHERE 1=1";
    
    $params = [];
    
    // Search filter (supports full-text search on product_code, product_name, description)
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (p.product_code LIKE ? OR p.product_name LIKE ? OR p.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    // Category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $sql .= " AND p.category = ?";
        $params[] = $_GET['category'];
    }
    
    // Status filter
    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $sql .= " AND p.is_active = ?";
        $params[] = $_GET['is_active'];
    }
    
    $sql .= " ORDER BY p.product_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Products retrieved successfully', $products);
    
} catch (Exception $e) {
    error_log('Error in products/list.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving products: ' . $e->getMessage());
}
