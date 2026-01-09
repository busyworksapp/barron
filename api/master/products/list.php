<?php
require_once '../../../config/config.php';
require_once '../../../classes/Database.php';

header('Content-Type: application/json');

requireLogin();
// Note: Product list needed for dropdowns across the system

try {
    $conn = Database::getInstance()->getConnection();
    
    // Build query with filters
    $sql = "SELECT p.*, 
            u.name as created_by_name
            FROM products p
            LEFT JOIN users u ON p.created_by = u.id
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
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    successResponse('Products retrieved successfully', $products);
    
} catch (Exception $e) {
    error_log('Error in products/list.php: ' . $e->getMessage());
    errorResponse('Error retrieving products: ' . $e->getMessage());
}
