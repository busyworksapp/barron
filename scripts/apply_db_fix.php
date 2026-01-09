<?php
/**
 * Apply Missing Tables Fix
 * Creates: notifications, defects, ncrs, maintenance_tasks
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<pre style='background: #1a1a1a; color: #0f0; padding: 20px; font-family: monospace;'>\n";
echo "===========================================\n";
echo "   APPLYING DATABASE FIXES\n";
echo "===========================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $success_count = 0;
    $error_count = 0;
    
    // Define SQL statements directly (avoid file parsing issues)
    $tables_to_create = [
        'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'defects' => "CREATE TABLE IF NOT EXISTS defects (
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
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
            FOREIGN KEY (reported_by) REFERENCES users(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_defect_number (defect_number),
            INDEX idx_job_id (job_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'ncrs' => "CREATE TABLE IF NOT EXISTS ncrs (
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
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
            FOREIGN KEY (reported_by) REFERENCES users(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_ncr_number (ncr_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'maintenance_tasks' => "CREATE TABLE IF NOT EXISTS maintenance_tasks (
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
            FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE SET NULL,
            FOREIGN KEY (reported_by) REFERENCES users(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_task_number (task_number),
            INDEX idx_machine_id (machine_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    echo "Creating " . count($tables_to_create) . " tables...\n\n";
    
    foreach ($tables_to_create as $table_name => $sql) {
        echo "Creating table: $table_name... ";
        try {
            $conn->exec($sql);
            echo "✓ SUCCESS\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
    
    // Insert test notification
    echo "\nInserting test notification... ";
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (1, 'system', 'Welcome', 'Your Barron Production System is now online!')");
        $stmt->execute();
        echo "✓ SUCCESS\n";
        $success_count++;
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n===========================================\n";
    echo "SUMMARY:\n";
    echo "===========================================\n";
    echo "Successful: $success_count\n";
    echo "Errors: $error_count\n\n";
    
    if ($error_count === 0) {
        echo "✓ ALL FIXES APPLIED SUCCESSFULLY!\n\n";
        
        // Verify tables now exist
        echo "Verifying tables...\n";
        $tables = ['notifications', 'defects', 'ncrs', 'maintenance_tasks'];
        foreach ($tables as $table) {
            $query = "SHOW TABLES LIKE '$table'";
            $stmt = $conn->query($query);
            $result = $stmt->fetch();
            if ($result) {
                echo "  ✓ $table - EXISTS\n";
            } else {
                echo "  ✗ $table - STILL MISSING!\n";
            }
        }
    } else {
        echo "⚠️  Some errors occurred. Check details above.\n";
    }
    
    echo "\n===========================================\n";
    echo "NEXT STEP:\n";
    echo "===========================================\n";
    echo "Clear your browser cache and reload the dashboard.\n";
    echo "All pages should now work!\n\n";
    
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
