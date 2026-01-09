<?php
/**
 * API Health Check
 * Tests all critical API endpoints to ensure they're working
 * Access at: /scripts/api_health_check.php
 */

// Prevent direct browser access for security
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html><html><head><title>API Health Check</title><style>';
    echo 'body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;}';
    echo '.pass{color:#0f0;} .fail{color:#f00;} .warn{color:#ff0;} h1{color:#0ff;}';
    echo '</style></head><body>';
}

echo "<h1>🔧 Barron System API Health Check</h1>\n";
echo "<p>Testing all critical API endpoints...</p>\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// Base URL for testing
$base_url = 'https://barron-production.up.railway.app';
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];
}

// Test endpoints
$endpoints = [
    'Dashboard Stats' => '/api/dashboard/stats.php',
    'Recent Activity' => '/api/dashboard/recent_activity.php',
    'Notifications List' => '/api/notifications/list.php',
    'Jobs List' => '/api/jobs/list.php',
    'Defects List' => '/api/defects/list.php',
    'NCR List' => '/api/ncr/list.php',
    'Maintenance Tasks' => '/api/maintenance/list.php',
];

function testEndpoint($name, $url) {
    global $passed, $failed, $warnings;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<div>";
    echo "<strong>Testing: $name</strong> ";
    
    if ($error) {
        echo "<span class='fail'>❌ FAIL</span> - Connection error: $error\n";
        $failed++;
    } elseif ($http_code === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<span class='pass'>✅ PASS</span> (HTTP $http_code)\n";
            $passed++;
        } else {
            echo "<span class='warn'>⚠️  WARN</span> - HTTP 200 but response may have issues\n";
            if ($data && isset($data['message'])) {
                echo "  Message: " . htmlspecialchars($data['message']) . "\n";
            }
            $warnings++;
        }
    } elseif ($http_code === 401 || $http_code === 403) {
        echo "<span class='warn'>⚠️  WARN</span> - Authentication required (HTTP $http_code)\n";
        echo "  This is expected if not logged in\n";
        $warnings++;
    } elseif ($http_code === 404) {
        echo "<span class='fail'>❌ FAIL</span> - Endpoint not found (HTTP $http_code)\n";
        $failed++;
    } elseif ($http_code >= 500) {
        echo "<span class='fail'>❌ FAIL</span> - Server error (HTTP $http_code)\n";
        $failed++;
    } else {
        echo "<span class='fail'>❌ FAIL</span> - HTTP $http_code\n";
        $failed++;
    }
    
    echo "  URL: $url\n";
    echo "</div>\n";
}

// Run tests
foreach ($endpoints as $name => $path) {
    testEndpoint($name, $base_url . $path);
}

// Database connection test
echo "\n<h2>📊 Database Connection</h2>\n";
try {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<div><span class='pass'>✅ PASS</span> - Database connected successfully</div>\n";
        $passed++;
        
        // Test table existence
        $tables = ['users', 'jobs', 'defects', 'ncrs', 'maintenance_tasks', 'notifications'];
        echo "<div>Testing tables: ";
        $missing_tables = [];
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            echo "<span class='pass'>✅ All core tables exist</span></div>\n";
            $passed++;
        } else {
            echo "<span class='fail'>❌ Missing tables: " . implode(', ', $missing_tables) . "</span></div>\n";
            $failed++;
        }
    }
} catch (Exception $e) {
    echo "<div><span class='fail'>❌ FAIL</span> - Database error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    $failed++;
}

// Summary
echo "\n<h2>📋 Summary</h2>\n";
echo "<div>";
echo "<span class='pass'>✅ Passed: $passed</span> | ";
echo "<span class='fail'>❌ Failed: $failed</span> | ";
echo "<span class='warn'>⚠️  Warnings: $warnings</span>\n";
echo "</div>\n";

$total = $passed + $failed + $warnings;
$pass_rate = $total > 0 ? round(($passed / $total) * 100) : 0;

echo "\n<div>";
if ($failed === 0) {
    echo "<h3 class='pass'>🎉 All critical tests passed! System is healthy.</h3>\n";
} else {
    echo "<h3 class='fail'>⚠️  Some tests failed. Review errors above.</h3>\n";
}
echo "</div>\n";

if (php_sapi_name() !== 'cli') {
    echo '</body></html>';
}

exit($failed > 0 ? 1 : 0);
