<?php
/**
 * Recent Activity API
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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $query = "SELECT 
                al.action,
                al.table_name,
                al.record_id,
                al.changed_at as created_at,
                CONCAT(e.first_name, ' ', e.last_name) as user_name,
                CASE 
                    WHEN al.action = 'insert' THEN CONCAT('Created new ', REPLACE(al.table_name, '_', ' '))
                    WHEN al.action = 'update' THEN CONCAT('Updated ', REPLACE(al.table_name, '_', ' '))
                    WHEN al.action = 'delete' THEN CONCAT('Deleted ', REPLACE(al.table_name, '_', ' '))
                END as description
              FROM audit_log al
              LEFT JOIN employees e ON al.changed_by = e.id
              ORDER BY al.changed_at DESC
              LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $activities = $stmt->fetchAll();
    
    successResponse('Activity loaded successfully', $activities);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
