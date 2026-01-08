<?php
/**
 * Logout API Endpoint
 */

// Start session
session_start();

// Load configuration
require_once '../../config/config.php';
require_once '../../config/database.php';

// Set JSON header
header('Content-Type: application/json');

try {
    $auth = new Auth();
    $result = $auth->logout();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
