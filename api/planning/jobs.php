<?php
/**
 * Barron Production Management System
 * Jobs API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Planning.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$planning = new Planning();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - List jobs or get single job details
    if ($method === 'GET') {
        // Check view permission
        if (!checkPermission('planning.view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        if (isset($_GET['id'])) {
            // Get single job details
            $job = $planning->getJobDetails($_GET['id']);
            
            if ($job) {
                $response = [
                    'success' => true,
                    'job' => $job
                ];
            } else {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Job not found'];
            }
        } else {
            // Get jobs list with filters
            $filters = [
                'order_id' => $_GET['order_id'] ?? null,
                'department_id' => $_GET['department_id'] ?? null,
                'operator_id' => $_GET['operator_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20
            ];
            
            $jobs = $planning->getJobs($filters);
            
            $response = [
                'success' => true,
                'jobs' => $jobs['data'],
                'pagination' => $jobs['pagination']
            ];
        }
    }
    
    // POST - Create new job or update job status
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Handle status update
        if (isset($input['action']) && $input['action'] === 'update_status') {
            // Check edit permission
            if (!checkPermission('planning.edit')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            if (empty($input['id']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Job ID and status required']);
                exit;
            }
            
            $result = $planning->updateJobStatus($input['id'], $input['status'], $input['notes'] ?? null);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Job status updated successfully'
                ];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to update job status'];
            }
        }
        // Handle bulk status update
        elseif (isset($input['action']) && $input['action'] === 'bulk_update') {
            // Check edit permission
            if (!checkPermission('planning.edit')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            if (empty($input['job_ids']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Job IDs and status required']);
                exit;
            }
            
            $success_count = 0;
            foreach ($input['job_ids'] as $job_id) {
                if ($planning->updateJobStatus($job_id, $input['status'], $input['notes'] ?? null)) {
                    $success_count++;
                }
            }
            
            $response = [
                'success' => true,
                'message' => "{$success_count} jobs updated successfully",
                'updated_count' => $success_count
            ];
        }
        // Handle job creation
        else {
            // Check create permission
            if (!checkPermission('planning.create')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            // Validate required fields
            if (empty($input['order_id']) || empty($input['department_id']) || empty($input['stage_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $job_id = $planning->createJob($input);
            
            if ($job_id) {
                $response = [
                    'success' => true,
                    'message' => 'Job created successfully',
                    'job_id' => $job_id
                ];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to create job'];
            }
        }
    }
    
    // PUT - Update job
    elseif ($method === 'PUT') {
        // Check edit permission
        if (!checkPermission('planning.edit')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job ID required']);
            exit;
        }
        
        $result = $planning->updateJob($input['id'], $input);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Job updated successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to update job'];
        }
    }
    
    // DELETE - Delete job
    elseif ($method === 'DELETE') {
        // Check delete permission
        if (!checkPermission('planning.delete')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job ID required']);
            exit;
        }
        
        // Soft delete by updating status to cancelled
        $result = $planning->updateJobStatus($input['id'], 'cancelled');
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Job cancelled successfully'
            ];
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Failed to cancel job'];
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
