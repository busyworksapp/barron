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
$required = ['product_id', 'product_code', 'product_name', 'category'];
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
    
    // Check if product exists
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$data['product_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo jsonResponse(false, 'Product not found');
        exit;
    }
    
    // Check for duplicate product code (excluding current product)
    $stmt = $db->prepare("SELECT id FROM products WHERE product_code = ? AND id != ?");
    $stmt->execute([$data['product_code'], $data['product_id']]);
    if ($stmt->fetch()) {
        echo jsonResponse(false, 'Product code already exists');
        exit;
    }
    
    // Convert specifications to JSON
    $specifications = null;
    if (isset($data['specifications']) && is_array($data['specifications']) && !empty($data['specifications'])) {
        $specifications = json_encode($data['specifications']);
    }
    
    // Update product
    $stmt = $db->prepare("
        UPDATE products SET 
            product_code = ?, 
            product_name = ?, 
            category = ?, 
            description = ?,
            specifications = ?, 
            is_active = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['product_code'],
        $data['product_name'],
        $data['category'],
        $data['description'] ?? null,
        $specifications,
        $data['is_active'] ?? 1,
        $data['product_id']
    ]);
    
    // Build change log
    $changes = [];
    foreach ($existing as $key => $value) {
        if (isset($data[$key]) && $data[$key] != $value && $key != 'updated_at') {
            if ($key == 'specifications') {
                $changes[] = "$key updated";
            } else {
                $changes[] = "$key: '$value' → '{$data[$key]}'";
            }
        }
    }
    
    // Log activity
    if (!empty($changes)) {
        logActivity('product_updated', 'products', $data['product_id'], 
            "Updated product: {$data['product_name']} - " . implode(', ', $changes));
    }
    
    echo jsonResponse(true, 'Product updated successfully');
    
} catch (Exception $e) {
    error_log('Error in products/update.php: ' . $e->getMessage());
    echo jsonResponse(false, 'Error updating product: ' . $e->getMessage());
}
