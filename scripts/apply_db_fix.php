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
    
    // Read SQL file
    $sql_file = __DIR__ . '/../database/fix_missing_tables.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   strpos($stmt, '--') !== 0 && 
                   strpos($stmt, '/*') !== 0;
        }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute...\n\n";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Extract table name for reporting
        if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
            $table_name = $matches[1];
            echo "Creating table: $table_name... ";
            
            try {
                $conn->exec($statement);
                echo "✓ SUCCESS\n";
                $success_count++;
            } catch (Exception $e) {
                echo "✗ ERROR: " . $e->getMessage() . "\n";
                $error_count++;
            }
        } elseif (preg_match('/INSERT INTO (\w+)/i', $statement, $matches)) {
            $table_name = $matches[1];
            echo "Inserting data into: $table_name... ";
            
            try {
                $conn->exec($statement);
                echo "✓ SUCCESS\n";
                $success_count++;
            } catch (Exception $e) {
                echo "✗ ERROR: " . $e->getMessage() . "\n";
                $error_count++;
            }
        } else {
            echo "Executing statement " . ($index + 1) . "... ";
            try {
                $conn->exec($statement);
                echo "✓ SUCCESS\n";
                $success_count++;
            } catch (Exception $e) {
                echo "✗ ERROR: " . $e->getMessage() . "\n";
                $error_count++;
            }
        }
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
