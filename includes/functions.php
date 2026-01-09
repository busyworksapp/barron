<?php
/**
 * Barron Production Management System
 * Helper Functions
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate unique ticket/reference number
 */
function generateTicketNumber($prefix = 'TKT') {
    return $prefix . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Calculate date difference in hours
 */
function getHoursDifference($start, $end) {
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    return abs($end_time - $start_time) / 3600;
}

/**
 * Check if user has permission
 */
function hasPermission($permission_code) {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    return in_array($permission_code, $_SESSION['user_permissions']);
}

/**
 * Check if user has role
 */
function hasRole($role_code) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role_code;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'department_id' => $_SESSION['department_id'] ?? null
    ];
}

/**
 * Log activity
 */
function logActivity($action, $table_name, $record_id, $old_values = null, $new_values = null) {
    try {
        // Activity logging disabled - no audit_log table in current schema
        // This is a placeholder for future audit trail implementation
        error_log("Activity: $action on $table_name (ID: $record_id) by user " . getCurrentUserId());
        
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

/**
 * Send notification
 */
function sendNotification($user_id, $type, $title, $message, $module = null, $reference_id = null, $reference_table = null, $priority = 'normal') {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Use actual notifications table schema: user_id, type, title, message, is_read, created_at
        $query = "INSERT INTO notifications (user_id, type, title, message) 
                  VALUES (:user_id, :type, :title, :message)";
        
        $stmt = $conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $type,
            ':title' => $title,
            ':message' => $message
        ]);
        
    } catch (Exception $e) {
        error_log("Notification failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount() {
    try {
        $user_id = getCurrentUserId();
        if (!$user_id) return 0;
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Use actual notifications table schema: user_id (not recipient_id)
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Success response
 */
function successResponse($message, $data = null) {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Error response
 */
function errorResponse($message, $status_code = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $status_code);
}

/**
 * Validate required fields
 */
function validateRequired($data, $required_fields) {
    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Check if date is within SLA
 */
function isWithinSLA($created_at, $sla_hours) {
    $created = strtotime($created_at);
    $now = time();
    $elapsed_hours = ($now - $created) / 3600;
    return $elapsed_hours <= $sla_hours;
}

/**
 * Calculate SLA due date
 */
function calculateSLADueDate($start_date, $hours) {
    return date('Y-m-d H:i:s', strtotime($start_date) + ($hours * 3600));
}

/**
 * Get status badge color
 */
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'scheduled' => 'info',
        'in_progress' => 'primary',
        'completed' => 'success',
        'approved' => 'success',
        'on_hold' => 'warning',
        'rejected' => 'danger',
        'cancelled' => 'secondary',
        'open' => 'info',
        'closed' => 'secondary',
        'escalated' => 'danger',
        'critical' => 'danger',
        'high' => 'warning',
        'medium' => 'info',
        'low' => 'secondary',
        'urgent' => 'danger',
        'normal' => 'info'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'R') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Pagination helper
 */
function paginate($total_records, $page = 1, $page_size = DEFAULT_PAGE_SIZE) {
    $total_pages = ceil($total_records / $page_size);
    $offset = ($page - 1) * $page_size;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'page_size' => $page_size,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $total_pages
    ];
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Require permission
 */
function requirePermission($permission_code) {
    if (!hasPermission($permission_code)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this resource.');
    }
}

/**
 * Generate username from name
 */
function generateUsername($first_name) {
    return strtolower($first_name) . '@barron';
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
}

/**
 * Upload file
 */
function uploadFile($file, $destination_folder) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file upload');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed with error code: ' . $file['error']);
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('File size exceeds maximum allowed size');
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_FILE_TYPES)) {
        throw new Exception('File type not allowed');
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $destination = BASE_PATH . '/' . $destination_folder . '/' . $new_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return $new_filename;
}
