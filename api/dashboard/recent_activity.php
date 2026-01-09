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
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Get recent activity from various tables
    $activities = [];
    
    // Recent jobs
    $query = "SELECT 'job' as type, id, job_number as reference, status, created_at, 
              'Job' as category FROM jobs ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->query($query);
    if ($stmt) {
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Recent defects
    $query = "SELECT 'defect' as type, id, defect_number as reference, severity as status, 
              created_at, 'Defect' as category FROM defects ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->query($query);
    if ($stmt) {
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Recent NCRs
    $query = "SELECT 'ncr' as type, id, ncr_number as reference, status, created_at, 
              'NCR' as category FROM ncrs ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->query($query);
    if ($stmt) {
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Sort by created_at
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to requested number
    $activities = array_slice($activities, 0, $limit);
    
    successResponse('Activity loaded successfully', $activities);
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
