<?php
require_once __DIR__ . '/../../classes/MaintenanceManager.php';
header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $maint = new MaintenanceManager($GLOBALS['db'] ?? null);
    
    if ($method === 'GET') {
        // Get task details
        if (isset($_GET['id'])) {
            $details = $maint->getTaskDetails((int)$_GET['id']);
            echo json_encode(['success' => true, 'data' => $details]);
            exit;
        }
        
        // Get statistics
        if (isset($_GET['stats'])) {
            $stats = $maint->getStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;
        }
        
        // Get calendar data
        if (isset($_GET['calendar'])) {
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            $tasks = $maint->getCalendar($start, $end);
            echo json_encode(['success' => true, 'data' => $tasks]);
            exit;
        }
        
        // Get list with filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['machine_id'])) $filters['machine_id'] = (int)$_GET['machine_id'];
        if (!empty($_GET['assigned_to'])) $filters['assigned_to'] = (int)$_GET['assigned_to'];
        if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
        if (!empty($_GET['overdue'])) $filters['overdue'] = true;
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $tasks = $maint->getTasks($filters, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $tasks]);
        exit;
    }
    
    if ($method === 'POST') {
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $payload = $decoded;
        }
        
        // Update task status
        if (isset($payload['update_status']) && isset($payload['id'])) {
            $ok = $maint->updateTaskStatus((int)$payload['id'], $payload['status'], $userId);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        // Log activity
        if (isset($payload['log_activity']) && isset($payload['task_id'])) {
            $logId = $maint->logActivity(
                (int)$payload['task_id'],
                $payload['notes'] ?? '',
                $userId,
                $payload['hours_spent'] ?? null
            );
            echo json_encode(['success' => true, 'id' => $logId]);
            exit;
        }
        
        // Create task
        $payload['created_by'] = $userId;
        $taskId = $maint->createTask($payload);
        echo json_encode(['success' => true, 'id' => $taskId]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
