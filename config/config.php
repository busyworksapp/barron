<?php
/**
 * Barron Production Management System
 * Application Configuration
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Timezone
date_default_timezone_set('Africa/Johannesburg');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Application Constants
define('APP_NAME', 'Barron Production Management System');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
define('OPERATOR_SESSION_LIFETIME', 28800); // 8 hours
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Pagination
define('DEFAULT_PAGE_SIZE', 50);
define('MAX_PAGE_SIZE', 500);

// File Upload
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('ALLOWED_FILE_TYPES', ['xlsx', 'xls', 'csv', 'pdf', 'jpg', 'jpeg', 'png']);

// Redis Configuration (for caching and sessions)
define('REDIS_HOST', 'caboose.proxy.rlwy.net');
define('REDIS_PORT', 39766);
define('REDIS_PASSWORD', 'maXFCPazHpxaASnHpDcszQQpTsfONXFE');

// Email Configuration
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@barron.co.za');
define('SMTP_FROM_NAME', 'Barron Production System');

// Modules
define('MODULES', [
    'master_data' => 'Master Data',
    'job_planning' => 'Job Planning',
    'defects' => 'Defects',
    'sop_failure' => 'SOP Failure & NCR',
    'maintenance' => 'Maintenance',
    'finance' => 'Finance',
    'operator' => 'Operator',
    'department' => 'Department Management'
]);

// Status Options
define('ORDER_STATUS', [
    'pending' => 'Pending',
    'scheduled' => 'Scheduled',
    'in_progress' => 'In Progress',
    'on_hold' => 'On Hold',
    'completed' => 'Completed',
    'rejected' => 'Rejected',
    'cancelled' => 'Cancelled'
]);

define('JOB_STATUS', [
    'scheduled' => 'Scheduled',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'on_hold' => 'On Hold',
    'cancelled' => 'Cancelled'
]);

define('REJECT_STATUS', [
    'pending_approval' => 'Pending Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'replacement_processed' => 'Replacement Processed',
    'no_stock' => 'No Stock'
]);

define('SOP_STATUS', [
    'open' => 'Open',
    'ncr_in_progress' => 'NCR In Progress',
    'ncr_completed' => 'NCR Completed',
    'rejected' => 'Rejected',
    'escalated' => 'Escalated to HOD',
    'closed' => 'Closed'
]);

define('MAINTENANCE_STATUS', [
    'open' => 'Open',
    'assigned' => 'Assigned',
    'in_progress' => 'In Progress',
    'awaiting_parts' => 'Awaiting Parts',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
]);

define('SEVERITY_LEVELS', [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'critical' => 'Critical'
]);

define('PRIORITY_LEVELS', [
    'low' => 'Low',
    'normal' => 'Normal',
    'high' => 'High',
    'urgent' => 'Urgent'
]);

// Autoloader
spl_autoload_register(function ($class) {
    $directories = [
        BASE_PATH . '/classes/',
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/utils/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helper Functions
require_once BASE_PATH . '/includes/functions.php';
