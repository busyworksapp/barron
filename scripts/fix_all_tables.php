<?php
/**
 * COMPLETE DATABASE FIX - Run this once to fix everything
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background: #1a1a1a; color: #0f0; padding: 20px; font-family: monospace;'>\n";
echo "===========================================\n";
echo "   COMPLETE DATABASE FIX\n";
echo "===========================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Step 1: Create notifications table
    echo "STEP 1: Creating notifications table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "✓ notifications table created\n\n";
    } catch (Exception $e) {
        echo "✓ notifications table already exists or error: " . $e->getMessage() . "\n\n";
    }
    
    // Step 2: Create defects table
    echo "STEP 2: Creating defects table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS defects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            defect_number VARCHAR(50) UNIQUE NOT NULL,
            job_id INT,
            product_id INT,
            department_id INT,
            defect_type VARCHAR(100) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            description TEXT NOT NULL,
            root_cause TEXT,
            corrective_action TEXT,
            status VARCHAR(50) DEFAULT 'open',
            severity VARCHAR(50) DEFAULT 'medium',
            reported_by INT NOT NULL,
            assigned_to INT,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_defect_number (defect_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "✓ defects table created\n\n";
    } catch (Exception $e) {
        echo "✓ defects table already exists or error: " . $e->getMessage() . "\n\n";
    }
    
    // Step 3: Create ncrs table
    echo "STEP 3: Creating ncrs table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS ncrs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ncr_number VARCHAR(50) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            department_id INT,
            severity VARCHAR(50) DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'open',
            root_cause TEXT,
            corrective_action TEXT,
            preventive_action TEXT,
            reported_by INT NOT NULL,
            assigned_to INT,
            verified_by INT,
            closed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_ncr_number (ncr_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "✓ ncrs table created\n\n";
    } catch (Exception $e) {
        echo "✓ ncrs table already exists or error: " . $e->getMessage() . "\n\n";
    }
    
    // Step 4: Create maintenance_tasks table
    echo "STEP 4: Creating maintenance_tasks table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS maintenance_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_number VARCHAR(50) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            machine_id INT,
            maintenance_type VARCHAR(50) NOT NULL,
            priority VARCHAR(50) DEFAULT 'normal',
            status VARCHAR(50) DEFAULT 'open',
            scheduled_date DATE,
            completed_date DATE,
            reported_by INT NOT NULL,
            assigned_to INT,
            estimated_hours DECIMAL(5,2),
            actual_hours DECIMAL(5,2),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_task_number (task_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "✓ maintenance_tasks table created\n\n";
    } catch (Exception $e) {
        echo "✓ maintenance_tasks table already exists or error: " . $e->getMessage() . "\n\n";
    }
    
    // Step 5: Insert test notification
    echo "STEP 5: Inserting test notification...\n";
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) 
                               SELECT 1, 'system', 'System Online', 'All systems operational' 
                               FROM DUAL 
                               WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = 1 LIMIT 1)");
        $stmt->execute();
        echo "✓ Test notification inserted\n\n";
    } catch (Exception $e) {
        echo "✓ Notification already exists or error: " . $e->getMessage() . "\n\n";
    }
    
    // Step 6: Verify all tables exist
    echo "STEP 6: Verifying tables...\n";
    $tables_to_check = ['users', 'notifications', 'defects', 'ncrs', 'maintenance_tasks', 
                        'jobs', 'departments', 'products', 'machines'];
    $all_good = true;
    
    foreach ($tables_to_check as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        if ($exists) {
            $count_stmt = $conn->query("SELECT COUNT(*) as cnt FROM $table");
            $count = $count_stmt->fetch()['cnt'];
            echo "  ✓ $table - EXISTS (rows: $count)\n";
        } else {
            echo "  ✗ $table - MISSING!\n";
            $all_good = false;
        }
    }
    
    echo "\n===========================================\n";
    if ($all_good) {
        echo "✓ ALL TABLES VERIFIED SUCCESSFULLY!\n";
        echo "===========================================\n\n";
        echo "NEXT STEPS:\n";
        echo "1. Clear browser cache (Ctrl+Shift+Delete)\n";
        echo "2. Reload the dashboard\n";
        echo "3. All pages should now work!\n\n";
    } else {
        echo "⚠️  SOME TABLES STILL MISSING\n";
        echo "===========================================\n";
        echo "Contact support if issues persist.\n\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ CRITICAL ERROR:\n";
    echo $e->getMessage() . "\n\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
