<?php
/**
 * Barron Production Management System
 * Operator Progress API Endpoint
 * Handles job progress updates and stage completion
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
    
    // POST - Update progress or complete stage
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'update_quantity':
                // Update completed quantity
                if (empty($input['job_id']) || !isset($input['completed_quantity'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Job ID and completed quantity required']);
                    exit;
                }
                
                $result = $workflow->updateProgress(
                    $input['job_id'],
                    $input['completed_quantity'],
                    $_SESSION['user_id'],
                    $input['notes'] ?? null
                );
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Progress updated successfully'
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to update progress'];
                }
                break;
            
            case 'complete_stage':
                // Complete current stage and move to next
                if (empty($input['job_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Job ID required']);
                    exit;
                }
                
                $result = $workflow->completeStage(
                    $input['job_id'],
                    $_SESSION['user_id'],
                    $input['notes'] ?? null
                );
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Stage completed successfully'
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to complete stage'];
                }
                break;
            
            case 'pause':
                // Pause job (unassign operator)
                if (empty($input['job_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Job ID required']);
                    exit;
                }
                
                // Simple unassignment
                $stmt = $pdo->prepare("
                    UPDATE jobs 
                    SET assigned_operator_id = NULL,
                        status = 'pending',
                        updated_at = NOW()
                    WHERE id = ? AND assigned_operator_id = ?
                ");
                $result = $stmt->execute([$input['job_id'], $_SESSION['user_id']]);
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Job paused successfully'
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to pause job'];
                }
                break;
            
            default:
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
