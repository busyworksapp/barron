<?php
/**
 * Notifications List API
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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    $query = "SELECT 
                id,
                title,
                message,
                type as notification_type,
                is_read,
                created_at
              FROM notifications
              WHERE user_id = :user_id
              ORDER BY is_read ASC, created_at DESC
              LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll();
    
    successResponse('Notifications loaded successfully', $notifications);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
