<?php
/**
 * Barron Production Management System
 * Order Import API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/OrderImporter.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$importer = new OrderImporter();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Check create permission
    if (!checkPermission('planning.create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Get import history
    if ($method === 'GET') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $history = $importer->getImportHistory($limit);
        
        $response = [
            'success' => true,
            'history' => $history
        ];
    }
    
    // POST - Handle file upload and import
    elseif ($method === 'POST') {
        $action = $_POST['action'] ?? 'import';
        
        if ($action === 'validate') {
            // Validate file before import
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                exit;
            }
            
            // Get column mapping
            $column_mapping = json_decode($_POST['column_mapping'] ?? '{}', true);
            
            if (empty($column_mapping)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Column mapping required']);
                exit;
            }
            
            // Move uploaded file to temp location
            $temp_path = sys_get_temp_dir() . '/' . uniqid('import_') . '.csv';
            move_uploaded_file($_FILES['file']['tmp_name'], $temp_path);
            
            // Validate file
            $validation = $importer->validateImportFile($temp_path, $column_mapping);
            
            // Clean up temp file
            unlink($temp_path);
            
            $response = [
                'success' => $validation['valid'],
                'validation' => $validation
            ];
        }
        elseif ($action === 'import') {
            // Import file
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                exit;
            }
            
            // Get column mapping
            $column_mapping = json_decode($_POST['column_mapping'] ?? '{}', true);
            
            if (empty($column_mapping)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Column mapping required']);
                exit;
            }
            
            // Move uploaded file to temp location
            $temp_path = sys_get_temp_dir() . '/' . uniqid('import_') . '.csv';
            move_uploaded_file($_FILES['file']['tmp_name'], $temp_path);
            
            // Import orders
            $result = $importer->importFromExcel($temp_path, $column_mapping);
            
            // Clean up temp file
            unlink($temp_path);
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => "Successfully imported {$result['imported']} of {$result['total_rows']} orders",
                    'imported_count' => $result['imported'],
                    'total_rows' => $result['total_rows'],
                    'errors' => $result['errors']
                ];
            } else {
                http_response_code(500);
                $response = [
                    'success' => false,
                    'message' => 'Import failed',
                    'errors' => $result['errors']
                ];
            }
        }
        else {
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    else {
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
