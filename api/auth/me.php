<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

try {
    $user = getCurrentUser();
    
    if (!$user) {
        echo jsonResponse(false, 'User not found');
        exit;
    }
    
    echo jsonResponse(true, 'User retrieved successfully', $user);
    
} catch (Exception $e) {
    error_log("Error in auth/me.php: " . $e->getMessage());
    echo jsonResponse(false, 'Error retrieving user: ' . $e->getMessage());
}
