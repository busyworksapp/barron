<?php
require_once __DIR__ . '/../../classes/NCRManager.php';
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
    $ncr = new NCRManager($GLOBALS['db'] ?? null);
    
    if ($method === 'GET') {
        // Get NCR details
        if (isset($_GET['id'])) {
            $details = $ncr->getNCRDetails((int)$_GET['id']);
            echo json_encode(['success' => true, 'data' => $details]);
            exit;
        }
        
        // Get statistics
        if (isset($_GET['stats'])) {
            $stats = $ncr->getStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;
        }
        
        // Get list with filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['department_id'])) $filters['department_id'] = (int)$_GET['department_id'];
        if (!empty($_GET['severity'])) $filters['severity'] = $_GET['severity'];
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $ncrs = $ncr->getNCRs($filters, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $ncrs]);
        exit;
    }
    
    if ($method === 'POST') {
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $payload = $decoded;
        }
        
        // Update status
        if (isset($payload['update_status']) && isset($payload['id'])) {
            $ok = $ncr->updateStatus(
                (int)$payload['id'],
                $payload['status'],
                $payload['notes'] ?? null,
                $userId
            );
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        // Update NCR
        if (isset($payload['id']) && !empty($payload['id'])) {
            $ok = $ncr->update((int)$payload['id'], $payload);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        // Create NCR
        $payload['reported_by'] = $userId;
        $ncrId = $ncr->create($payload);
        echo json_encode(['success' => true, 'id' => $ncrId]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
