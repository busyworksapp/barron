<?php
/**
 * Login API Endpoint
 */

// Start session
session_start();

// Load configuration
require_once '../../config/config.php';
require_once '../../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    if (empty($input['username']) || empty($input['password'])) {
        throw new Exception('Username and password are required');
    }
    
    // Sanitize inputs
    $username = sanitize($input['username']);
    $password = $input['password']; // Don't sanitize password
    
    // Attempt login
    $auth = new Auth();
    $result = $auth->login($username, $password);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
