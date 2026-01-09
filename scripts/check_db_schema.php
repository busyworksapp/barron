<?php
/**
 * Database Schema Checker
 * Determines which tables exist in production
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<pre style='background: #1a1a1a; color: #0f0; padding: 20px; font-family: monospace;'>\n";
    echo "===========================================\n";
    echo "   DATABASE SCHEMA CHECK\n";
    echo "===========================================\n\n";
    
    // Check for key tables
    $tables_to_check = [
        'users',
        'employees',
        'departments',
        'jobs',
        'defects',
        'ncrs',
        'maintenance_tasks',
        'notifications',
        'orders',
        'internal_rejects',
        'customer_returns',
        'sop_failures',
        'ncr_reports',
        'maintenance_tickets',
        'audit_log',
        'activity_logs',
        'products',
        'machines'
    ];
    
    $existing_tables = [];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $conn->query($query);
        $result = $stmt->fetch();
        
        if ($result) {
            $existing_tables[] = $table;
            echo "✓ $table - EXISTS\n";
            
            // Get row count
            $count_query = "SELECT COUNT(*) as count FROM $table";
            $count_stmt = $conn->query($count_query);
            $count_result = $count_stmt->fetch();
            echo "  └─ Rows: " . $count_result['count'] . "\n\n";
        } else {
            $missing_tables[] = $table;
            echo "✗ $table - MISSING\n\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "SUMMARY:\n";
    echo "===========================================\n";
    echo "Tables Found: " . count($existing_tables) . "\n";
    echo "Tables Missing: " . count($missing_tables) . "\n\n";
    
    // Critical determination
    echo "===========================================\n";
    echo "CRITICAL FINDINGS:\n";
    echo "===========================================\n\n";
    
    $has_users = in_array('users', $existing_tables);
    $has_employees = in_array('employees', $existing_tables);
    
    if ($has_users && !$has_employees) {
        echo "⚠️  SCHEMA TYPE: USERS-BASED\n";
        echo "    Your database uses 'users' table.\n";
        echo "    Many API files incorrectly reference 'employees'.\n";
        echo "    ACTION REQUIRED: Fix all employees references → users\n\n";
    } elseif ($has_employees && !$has_users) {
        echo "⚠️  SCHEMA TYPE: EMPLOYEES-BASED\n";
        echo "    Your database uses 'employees' table.\n";
        echo "    Your auth system references 'users'.\n";
        echo "    ACTION REQUIRED: Fix all users references → employees\n\n";
    } elseif ($has_users && $has_employees) {
        echo "⚠️  SCHEMA TYPE: BOTH TABLES EXIST\n";
        echo "    Both 'users' and 'employees' exist.\n";
        echo "    This may cause confusion.\n";
        echo "    ACTION REQUIRED: Decide on one primary table\n\n";
    } else {
        echo "❌ CRITICAL: Neither 'users' nor 'employees' exist!\n";
        echo "   Database may not be initialized.\n\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre style='background: #1a1a1a; color: #f00; padding: 20px; font-family: monospace;'>";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "</pre>";
}
