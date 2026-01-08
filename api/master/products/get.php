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

if (!isset($_GET['id'])) {
    echo jsonResponse(false, 'Product ID is required');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo jsonResponse(false, 'Product not found');
        exit;
    }
    
    // Parse JSON specifications if exists
    if ($product['specifications']) {
        $product['specifications'] = json_decode($product['specifications'], true);
    }
    
    echo jsonResponse(true, 'Product retrieved successfully', $product);
    
} catch (Exception $e) {
    error_log('Error in products/get.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving product: ' . $e->getMessage());
}
