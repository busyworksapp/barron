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
        if ($path === 'job') {
            // Get job costing breakdown
            $jobId = (int)($_GET['job_id'] ?? 0);
            if (!$jobId) {
                throw new Exception('Job ID required');
            }
            
            $cost = $bomManager->calculateJobCost($jobId);
            echo json_encode(['success' => true, 'data' => $cost]);
            
        } elseif ($path === 'export') {
            // Export job costing data for accounting
            if (!hasPermission('admin') && !hasPermission('manager')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $jobId = (int)($_GET['job_id'] ?? 0);
            if (!$jobId) {
                throw new Exception('Job ID required');
            }
            
            $export = $bomManager->exportJobCostingData($jobId);
            echo json_encode(['success' => true, 'data' => $export]);
            
        } elseif ($path === 'summary') {
            // Get financial summary for date range
            if (!hasPermission('admin') && !hasPermission('manager')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $summary = $bomManager->getFinancialSummary($startDate, $endDate);
            echo json_encode(['success' => true, 'data' => $summary]);
            
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
