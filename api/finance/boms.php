<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/BOMManager.php';

header('Content-Type: application/json');

requireLogin();
$bomManager = new BOMManager($db);

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        if ($path === 'list') {
            // List BOMs for a product
            $productId = (int)($_GET['product_id'] ?? 0);
            if (!$productId) {
                throw new Exception('Product ID required');
            }
            
            $boms = $bomManager->getBOMs($productId);
            echo json_encode(['success' => true, 'data' => $boms]);
            
        } elseif ($path === 'detail') {
            // Get BOM details with items
            $bomId = (int)($_GET['id'] ?? 0);
            if (!$bomId) {
                throw new Exception('BOM ID required');
            }
            
            $bom = $bomManager->getBOMDetails($bomId);
            if (!$bom) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'BOM not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $bom]);
            
        } elseif ($path === 'requirements') {
            // Calculate material requirements
            $productId = (int)($_GET['product_id'] ?? 0);
            $quantity = (float)($_GET['quantity'] ?? 0);
            
            if (!$productId || !$quantity) {
                throw new Exception('Product ID and quantity required');
            }
            
            $requirements = $bomManager->calculateMaterialRequirements($productId, $quantity);
            echo json_encode(['success' => true, 'data' => $requirements]);
            
        } else {
            throw new Exception('Invalid action');
        }
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($path === 'create') {
            // Create new BOM
            if (!hasPermission('admin') && !hasPermission('planner')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $productId = (int)($data['product_id'] ?? 0);
            $version = $data['version'] ?? '1.0';
            
            if (!$productId) {
                throw new Exception('Product ID required');
            }
            
            $bomId = $bomManager->createBOM($productId, $version);
            echo json_encode(['success' => true, 'bom_id' => $bomId]);
            
        } elseif ($path === 'add_item') {
            // Add item to BOM
            if (!hasPermission('admin') && !hasPermission('planner')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $bomId = (int)($data['bom_id'] ?? 0);
            $materialId = (int)($data['material_id'] ?? 0);
            $quantity = (float)($data['quantity'] ?? 0);
            $unitCost = isset($data['unit_cost']) ? (float)$data['unit_cost'] : null;
            $sequence = isset($data['sequence']) ? (int)$data['sequence'] : null;
            
            if (!$bomId || !$materialId || !$quantity) {
                throw new Exception('BOM ID, material ID, and quantity required');
            }
            
            $itemId = $bomManager->addBOMItem($bomId, $materialId, $quantity, $unitCost, $sequence);
            echo json_encode(['success' => true, 'item_id' => $itemId]);
            
        } elseif ($path === 'update_item') {
            // Update BOM item
            if (!hasPermission('admin') && !hasPermission('planner')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $itemId = (int)($data['item_id'] ?? 0);
            $quantity = (float)($data['quantity'] ?? 0);
            $unitCost = isset($data['unit_cost']) ? (float)$data['unit_cost'] : null;
            
            if (!$itemId || !$quantity) {
                throw new Exception('Item ID and quantity required');
            }
            
            $bomManager->updateBOMItem($itemId, $quantity, $unitCost);
            echo json_encode(['success' => true]);
            
        } elseif ($path === 'delete_item') {
            // Delete BOM item
            if (!hasPermission('admin') && !hasPermission('planner')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $itemId = (int)($data['item_id'] ?? 0);
            if (!$itemId) {
                throw new Exception('Item ID required');
            }
            
            $bomManager->deleteBOMItem($itemId);
            echo json_encode(['success' => true]);
            
        } elseif ($path === 'update_status') {
            // Update BOM status
            if (!hasPermission('admin') && !hasPermission('planner')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $bomId = (int)($data['bom_id'] ?? 0);
            $status = $data['status'] ?? '';
            
            if (!$bomId || !$status) {
                throw new Exception('BOM ID and status required');
            }
            
            $bomManager->updateBOMStatus($bomId, $status);
            echo json_encode(['success' => true]);
            
        } else {
            throw new Exception('Invalid action');
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
