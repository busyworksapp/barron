<?php
/**
 * Barron Production Management System
 * Operator Defect Reporting API Endpoint
 * Quick defect reporting from production floor
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/OperatorWorkflow.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$workflow = new OperatorWorkflow();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // POST - Report defect
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($input['job_id']) || empty($input['quantity']) || empty($input['description'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job ID, quantity, and description required']);
            exit;
        }
        
        // Prepare defect data
        $defectData = [
            'job_id' => $input['job_id'],
            'quantity' => $input['quantity'],
            'severity' => $input['severity'] ?? 'medium',
            'description' => $input['description'],
            'requires_replacement' => $input['requires_replacement'] ?? 0,
            'operator_id' => $_SESSION['user_id']
        ];
        
        // Report defect
        $defect_id = $workflow->reportDefect($defectData);
        
        if ($defect_id) {
            $response = [
                'success' => true,
                'message' => 'Defect reported successfully',
                'defect_id' => $defect_id
            ];
            
            // If replacement required, notify user
            if (!empty($input['requires_replacement'])) {
                $response['message'] .= ' - Replacement ticket created and sent for approval';
            }
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to report defect'];
        }
    }
    
    // GET - Get defects for job
    elseif ($method === 'GET') {
        if (empty($_GET['job_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job ID required']);
            exit;
        }
        
        // Get defects for specific job
        $stmt = $pdo->prepare("
            SELECT 
                d.id,
                d.defect_number,
                d.quantity,
                d.severity,
                d.description,
                d.requires_replacement,
                d.status,
                d.created_at,
                u.username as reported_by_name
            FROM defects d
            LEFT JOIN users u ON d.reported_by = u.id
            WHERE d.job_id = ?
            ORDER BY d.created_at DESC
        ");
        
        $stmt->execute([$_GET['job_id']]);
        $defects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'defects' => $defects
        ];
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
