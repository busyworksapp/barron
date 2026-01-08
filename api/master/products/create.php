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
$required = ['product_code', 'product_name', 'category'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        exit;
    }
}

// Validate category
$validCategories = ['apparel', 'accessories', 'footwear', 'headwear', 'bags', 'other'];
if (!in_array($data['category'], $validCategories)) {
    echo jsonResponse(false, 'Invalid category');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check for duplicate product code
    $stmt = $db->prepare("SELECT id FROM products WHERE product_code = ?");
    $stmt->execute([$data['product_code']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Product code already exists');
        exit;
    }
    
    // Convert specifications to JSON
    $specifications = null;
    if (isset($data['specifications']) && is_array($data['specifications']) && !empty($data['specifications'])) {
        $specifications = json_encode($data['specifications']);
    }
    
    // Insert product
    $stmt = $db->prepare("
        INSERT INTO products (
            product_code, product_name, category, description,
            specifications, is_active, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['product_code'],
        $data['product_name'],
        $data['category'],
        $data['description'] ?? null,
        $specifications,
        $data['is_active'] ?? 1,
        getCurrentUser()['id']
    ]);
    
    $productId = $db->lastInsertId();
    
    // Log activity
    logActivity('product_created', 'products', $productId, 
        "Created product: {$data['product_name']} ({$data['product_code']})");
    
    echo jsonResponse(true, 'Product created successfully', ['id' => $productId]);
    
} catch (Exception $e) {
    error_log('Error in products/create.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error creating product: ' . $e->getMessage());
}
