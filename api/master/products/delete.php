<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    echo jsonResponse(false, 'Unauthorized');
    exit;
}

if (!hasPermission('master.delete')) {
    echo jsonResponse(false, 'Insufficient permissions');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'])) {
    echo jsonResponse(false, 'Product ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get product details before deleting
    $stmt = $db->prepare("SELECT product_code, product_name FROM products WHERE id = ?");
    $stmt->execute([$data['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo jsonResponse(false, 'Product not found');
        exit;
    }
    
    // Check for relationships
    $checks = [
        ['table' => 'order_items', 'field' => 'product_id', 'message' => 'order items'],
        ['table' => 'job_schedules', 'field' => 'product_id', 'message' => 'job schedules'],
        ['table' => 'bill_of_materials', 'field' => 'product_id', 'message' => 'bill of materials entries'],
        ['table' => 'internal_rejects', 'field' => 'product_id', 'message' => 'internal reject reports'],
        ['table' => 'customer_returns', 'field' => 'product_id', 'message' => 'customer return records']
    ];
    
    foreach ($checks as $check) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$check['table']} WHERE {$check['field']} = ?");
        $stmt->execute([$data['product_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo jsonResponse(false, "Cannot delete product. Associated with {$count} {$check['message']}. Consider marking as inactive instead.");
            exit;
        }
    }
    
    // Delete product
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$data['product_id']]);
    
    // Log activity
    logActivity('product_deleted', 'products', $data['product_id'], 
        "Deleted product: {$product['product_name']} ({$product['product_code']})");
    
    echo jsonResponse(true, 'Product deleted successfully');
    
} catch (Exception $e) {
    error_log('Error in products/delete.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error deleting product: ' . $e->getMessage());
}
