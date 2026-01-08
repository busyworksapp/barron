<?php
/**
 * Mark Notification as Read API
 */

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

try {
    requireLogin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['notification_id'])) {
        throw new Exception('Notification ID is required');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $user_id = getCurrentUserId();
    $notification_id = (int)$input['notification_id'];
    
    $query = "UPDATE notifications 
              SET is_read = 1, read_at = CURRENT_TIMESTAMP 
              WHERE id = :notification_id AND recipient_id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':notification_id' => $notification_id,
        ':user_id' => $user_id
    ]);
    
    successResponse('Notification marked as read');
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
