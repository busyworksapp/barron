<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('planning.view')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Build query with filters
    $sql = "SELECT o.*, 
            CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name,
            COUNT(DISTINCT oi.id) as item_count,
            COALESCE(SUM(oi.quantity), 0) as total_quantity
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN employees creator ON o.created_by = creator.id
            WHERE 1=1";
    
    $params = [];
    
    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_ref LIKE ? OR o.po_number LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    // Status filter
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND o.status = ?";
        $params[] = $_GET['status'];
    }
    
    // Date range filter
    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
        $sql .= " AND o.due_date >= ?";
        $params[] = $_GET['date_from'];
    }
    
    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
        $sql .= " AND o.due_date <= ?";
        $params[] = $_GET['date_to'];
    }
    
    $sql .= " GROUP BY o.id ORDER BY o.order_date DESC, o.id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo jsonResponse(true, 'Orders retrieved successfully', $orders);
    
} catch (Exception $e) {
    error_log('Error in orders/list.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving orders: ' . $e->getMessage());
}
