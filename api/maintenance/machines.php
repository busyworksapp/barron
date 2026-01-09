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
    
    $method = $_SERVER['REQUEST_METHOD'];
    $maint = new MaintenanceManager($GLOBALS['db'] ?? null);
    
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $machine = $maint->getMachine((int)$_GET['id']);
            echo json_encode(['success' => true, 'data' => $machine]);
            exit;
        }
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $machines = $maint->getMachines($limit, $offset);
        echo json_encode(['success' => true, 'data' => $machines]);
        exit;
    }
    
    if ($method === 'POST') {
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $payload = $decoded;
        }
        
        // Update machine
        if (isset($payload['id']) && !empty($payload['id'])) {
            $ok = $maint->updateMachine((int)$payload['id'], $payload);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        // Create machine
        $machineId = $maint->createMachine($payload);
        echo json_encode(['success' => true, 'id' => $machineId]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
