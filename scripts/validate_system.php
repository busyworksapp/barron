#!/usr/bin/env php
<?php
/**
 * System Validation Script
 * Run this script to validate the Barron Production Management System installation
 * 
 * Usage: php scripts/validate_system.php
 */

echo "\n";
echo "========================================\n";
echo " BARRON SYSTEM VALIDATION\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];
$passed = 0;
$failed = 0;

// Test 1: PHP Version
echo "✓ Checking PHP version... ";
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo "✅ " . PHP_VERSION . "\n";
    $passed++;
} else {
    echo "❌ PHP 8.0+ required, found " . PHP_VERSION . "\n";
    $errors[] = "Upgrade PHP to 8.0 or higher";
    $failed++;
}

// Test 2: Required Extensions
echo "✓ Checking PHP extensions... ";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
$missing = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}
if (empty($missing)) {
    echo "✅ All required extensions loaded\n";
    $passed++;
} else {
    echo "❌ Missing: " . implode(', ', $missing) . "\n";
    $errors[] = "Install missing PHP extensions: " . implode(', ', $missing);
    $failed++;
}

// Test 3: Database Connection
echo "✓ Checking database connection... ";
require_once __DIR__ . '/../includes/config.php';
try {
    $stmt = $db->query("SELECT 1");
    echo "✅ Connected\n";
    $passed++;
} catch (PDOException $e) {
    echo "❌ Connection failed\n";
    $errors[] = "Database connection error: " . $e->getMessage();
    $failed++;
}

// Test 4: Required Tables
if ($db) {
    echo "✓ Checking database tables... ";
    $required_tables = [
        'users', 'products', 'departments', 'production_stages',
        'orders', 'jobs', 'defects', 'replacement_tickets',
        'ncrs', 'ncr_attachments', 'machines', 'maintenance_tasks',
        'maintenance_logs', 'boms', 'bom_items', 'notifications'
    ];
    
    $stmt = $db->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    
    if (empty($missing_tables)) {
        echo "✅ " . count($required_tables) . " tables found\n";
        $passed++;
    } else {
        echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n";
        $errors[] = "Run database schema migrations";
        $failed++;
    }
}

// Test 5: Directory Permissions
echo "✓ Checking directory permissions... ";
$required_dirs = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads/ncr_attachments'
];

$permission_issues = [];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        $permission_issues[] = $dir;
    }
}

if (empty($permission_issues)) {
    echo "✅ All directories writable\n";
    $passed++;
} else {
    echo "❌ Not writable: " . implode(', ', $permission_issues) . "\n";
    $errors[] = "Set write permissions: chmod -R 775 uploads/ logs/";
    $failed++;
}

// Test 6: Core Classes
echo "✓ Checking core classes... ";
$required_classes = [
    __DIR__ . '/../classes/Planning.php',
    __DIR__ . '/../classes/Defects.php',
    __DIR__ . '/../classes/NCRManager.php',
    __DIR__ . '/../classes/MaintenanceManager.php',
    __DIR__ . '/../classes/BOMManager.php',
    __DIR__ . '/../classes/MasterData.php',
    __DIR__ . '/../classes/NotificationService.php'
];

$missing_classes = [];
foreach ($required_classes as $class_file) {
    if (!file_exists($class_file)) {
        $missing_classes[] = basename($class_file);
    }
}

if (empty($missing_classes)) {
    echo "✅ All core classes present\n";
    $passed++;
} else {
    echo "❌ Missing: " . implode(', ', $missing_classes) . "\n";
    $errors[] = "Core class files missing - reinstall application";
    $failed++;
}

// Test 7: API Endpoints
echo "✓ Checking API endpoints... ";
$api_dirs = [
    __DIR__ . '/../api/jobs',
    __DIR__ . '/../api/defects',
    __DIR__ . '/../api/ncr',
    __DIR__ . '/../api/maintenance',
    __DIR__ . '/../api/finance',
    __DIR__ . '/../api/master',
    __DIR__ . '/../api/notifications'
];

$missing_apis = [];
foreach ($api_dirs as $api_dir) {
    if (!is_dir($api_dir)) {
        $missing_apis[] = basename($api_dir);
    }
}

if (empty($missing_apis)) {
    echo "✅ All API directories present\n";
    $passed++;
} else {
    echo "⚠️  Missing API directories: " . implode(', ', $missing_apis) . "\n";
    $warnings[] = "Some API directories missing - features may not work";
    $passed++;
}

// Test 8: Configuration
echo "✓ Checking configuration... ";
if (file_exists(__DIR__ . '/../.env')) {
    echo "✅ .env file exists\n";
    $passed++;
} else {
    echo "⚠️  .env file not found\n";
    $warnings[] = "Create .env file from .env.example for custom configuration";
    $passed++;
}

// Test 9: Seed Data
if ($db) {
    echo "✓ Checking seed data... ";
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $admin_exists = $stmt->fetchColumn() > 0;
        
        if ($admin_exists) {
            echo "✅ Admin user exists\n";
            $passed++;
        } else {
            echo "⚠️  Admin user not found\n";
            $warnings[] = "Run seed_master_data.sql to create default users";
            $passed++;
        }
    } catch (PDOException $e) {
        echo "⚠️  Could not check seed data\n";
        $warnings[] = "Verify seed data loaded correctly";
        $passed++;
    }
}

// Test 10: File Upload Configuration
echo "✓ Checking file upload configuration... ";
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
if (intval($upload_max) >= 10 && intval($post_max) >= 10) {
    echo "✅ Upload limits OK ($upload_max / $post_max)\n";
    $passed++;
} else {
    echo "⚠️  Upload limits: $upload_max / $post_max\n";
    $warnings[] = "Consider increasing upload_max_filesize and post_max_size to 10M+";
    $passed++;
}

// Results Summary
echo "\n========================================\n";
echo " VALIDATION RESULTS\n";
echo "========================================\n\n";

echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";

if (!empty($warnings)) {
    echo "⚠️  Warnings: " . count($warnings) . "\n";
}

echo "\n";

if ($failed === 0) {
    echo "🎉 SYSTEM VALIDATION SUCCESSFUL!\n";
    echo "   All critical checks passed.\n";
    echo "   System is ready for use.\n\n";
    
    if (!empty($warnings)) {
        echo "⚠️  RECOMMENDATIONS:\n";
        foreach ($warnings as $i => $warning) {
            echo "   " . ($i + 1) . ". $warning\n";
        }
        echo "\n";
    }
    
    echo "Next steps:\n";
    echo "1. Login at: http://localhost/pages/auth/login.php\n";
    echo "2. Default credentials: admin / password\n";
    echo "3. Change default passwords immediately!\n";
    exit(0);
} else {
    echo "❌ SYSTEM VALIDATION FAILED!\n";
    echo "   Critical issues must be resolved.\n\n";
    
    echo "ERRORS TO FIX:\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i + 1) . ". $error\n";
    }
    echo "\n";
    
    if (!empty($warnings)) {
        echo "WARNINGS:\n";
        foreach ($warnings as $i => $warning) {
            echo "   " . ($i + 1) . ". $warning\n";
        }
        echo "\n";
    }
    
    exit(1);
}
