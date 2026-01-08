<?php
/**
 * Notification Count API
 */

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    requireLogin();
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $user_id = getCurrentUserId();
    
    $query = "SELECT COUNT(*) as count 
              FROM notifications 
              WHERE recipient_id = :user_id AND is_read = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    $result = $stmt->fetch();
    
    successResponse('Count loaded successfully', ['count' => (int)$result['count']]);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
