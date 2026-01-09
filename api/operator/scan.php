<?php
/**
 * Barron Production Management System
 * Operator Scan API Endpoint
 * Handles job scanning and retrieval
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
    
    // GET - Scan job or get available jobs
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'scan';
        
        switch ($action) {
            case 'scan':
                // Scan job by job number
                if (empty($_GET['job_number'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Job number required']);
                    exit;
                }
                
                $job = $workflow->scanJob($_GET['job_number']);
                
                if ($job) {
                    $response = [
                        'success' => true,
                        'job' => $job
                    ];
                } else {
                    http_response_code(404);
                    $response = ['success' => false, 'message' => 'Job not found'];
                }
                break;
            
            case 'my_jobs':
                // Get operator's active jobs
                $jobs = $workflow->getOperatorJobs($_SESSION['user_id']);
                
                $response = [
                    'success' => true,
                    'jobs' => $jobs
                ];
                break;
            
            case 'available':
                // Get available jobs for department
                if (empty($_GET['department_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Department ID required']);
                    exit;
                }
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $jobs = $workflow->getAvailableJobs($_GET['department_id'], $limit);
                
                $response = [
                    'success' => true,
                    'jobs' => $jobs
                ];
                break;
            
            case 'stats':
                // Get operator statistics
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                
                $stats = $workflow->getOperatorStats($_SESSION['user_id'], $date_from, $date_to);
                
                if ($stats) {
                    $response = [
                        'success' => true,
                        'statistics' => $stats
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'message' => 'Failed to load statistics'];
                }
                break;
            
            default:
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    // POST - Start job
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'start';
        
        if ($action === 'start') {
            // Start working on job
            if (empty($input['job_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Job ID required']);
                exit;
            }
            
            $result = $workflow->startJob(
                $input['job_id'], 
                $_SESSION['user_id'],
                $input['stage_id'] ?? null
            );
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Job started successfully'
                ];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to start job'];
            }
        } else {
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
