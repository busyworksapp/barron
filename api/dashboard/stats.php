<?php
/**
 * Dashboard Stats API
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
    $stats = [];
    
    // Active Jobs
    $query = "SELECT COUNT(*) as count FROM jobs WHERE status IN ('scheduled', 'in_progress')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['active_jobs'] = $result ? $result['count'] : 0;
    
    // Pending Defects
    $query = "SELECT COUNT(*) as count FROM defects WHERE status = 'open'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['pending_defects'] = $result ? $result['count'] : 0;
    
    // Open Maintenance Tasks
    $query = "SELECT COUNT(*) as count FROM maintenance_tasks WHERE status IN ('scheduled', 'in_progress')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['open_maintenance'] = $result ? $result['count'] : 0;
    
    // Open NCRs
    $query = "SELECT COUNT(*) as count FROM ncrs WHERE status IN ('draft', 'submitted', 'under_review')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['open_ncrs'] = $result ? $result['count'] : 0;
    
    // Unread Notifications
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['unread_notifications'] = $result ? $result['count'] : 0;
    
    successResponse('Stats loaded successfully', $stats);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
