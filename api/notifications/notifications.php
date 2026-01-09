<?php
require_once __DIR__ . '/../../classes/NotificationService.php';
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
    $notif = new NotificationService($GLOBALS['db'] ?? null);
    
    if ($method === 'GET') {
        // Get unread notifications or all notifications
        if (isset($_GET['unread_count'])) {
            $count = $notif->getUnreadCount($userId);
            echo json_encode(['success' => true, 'count' => $count]);
            exit;
        }
        
        if (isset($_GET['unread'])) {
            $notifications = $notif->getUnread($userId, 50);
            echo json_encode(['success' => true, 'data' => $notifications]);
            exit;
        }
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $notifications = $notif->getAll($userId, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $notifications]);
        exit;
    }
    
    if ($method === 'POST') {
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $payload = $decoded;
        }
        
        // Mark as read
        if (isset($payload['mark_read']) && isset($payload['id'])) {
            $ok = $notif->markRead((int)$payload['id']);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        // Mark all as read
        if (isset($payload['mark_all_read'])) {
            $ok = $notif->markAllRead($userId);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
