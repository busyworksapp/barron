<?php
/**
 * ONE-CLICK FIX - Creates all missing tables
 * Direct database connection - no dependencies
 */

// Database credentials from Railway environment variables
$host = getenv('MYSQLHOST') ?: 'caboose.proxy.rlwy.net';
$port = getenv('MYSQLPORT') ?: '20038';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'wvkZIECDnHQqGFyOnOufNpcaYNtDMTHx';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h1>Creating Tables...</h1><pre>";
    
    // Create notifications
    echo "Creating notifications... ";
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "OK\n";
    
    // Create defects
    echo "Creating defects... ";
    $conn->exec("CREATE TABLE IF NOT EXISTS defects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        defect_number VARCHAR(50) UNIQUE NOT NULL,
        job_id INT,
        product_id INT,
        department_id INT,
        defect_type VARCHAR(100) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        description TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'open',
        severity VARCHAR(50) DEFAULT 'medium',
        reported_by INT NOT NULL,
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "OK\n";
    
    // Create ncrs
    echo "Creating ncrs... ";
    $conn->exec("CREATE TABLE IF NOT EXISTS ncrs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ncr_number VARCHAR(50) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        department_id INT,
        severity VARCHAR(50) DEFAULT 'medium',
        status VARCHAR(50) DEFAULT 'open',
        reported_by INT NOT NULL,
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "OK\n";
    
    // Create maintenance_tasks
    echo "Creating maintenance_tasks... ";
    $conn->exec("CREATE TABLE IF NOT EXISTS maintenance_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_number VARCHAR(50) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        machine_id INT,
        maintenance_type VARCHAR(50) NOT NULL,
        priority VARCHAR(50) DEFAULT 'normal',
        status VARCHAR(50) DEFAULT 'open',
        reported_by INT NOT NULL,
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "OK\n";
    
    // Insert test notification
    echo "Inserting test notification... ";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (1, 'system', 'Welcome', 'System is online!')");
    $stmt->execute();
    echo "OK\n";
    
    echo "\n✓ ALL TABLES CREATED!\n";
    echo "\nNow reload your dashboard and all pages should work!\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1><pre>";
    echo $e->getMessage();
    echo "</pre>";
}
