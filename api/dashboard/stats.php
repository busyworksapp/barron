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
    
    // Active Orders
    if (hasPermission('planning.view')) {
        $query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('scheduled', 'in_progress')";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['active_orders'] = $result['count'];
    }
    
    // Pending Rejects
    if (hasPermission('defects.view')) {
        $query = "SELECT COUNT(*) as count FROM internal_rejects WHERE status = 'pending_approval'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['pending_rejects'] = $result['count'];
    }
    
    // Open Maintenance Tickets
    if (hasPermission('maintenance.view')) {
        $query = "SELECT COUNT(*) as count FROM maintenance_tickets WHERE status IN ('open', 'assigned', 'in_progress')";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['open_maintenance'] = $result['count'];
    }
    
    // Open SOP Tickets
    if (hasPermission('sop.view')) {
        $query = "SELECT COUNT(*) as count FROM sop_failures WHERE status IN ('open', 'ncr_in_progress', 'escalated')";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['open_sop'] = $result['count'];
    }
    
    successResponse('Stats loaded successfully', $stats);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
