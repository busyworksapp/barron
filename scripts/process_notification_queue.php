#!/usr/bin/env php
<?php
/**
 * Notification Queue Processor
 * Run this script via cron/scheduler to process pending email/SMS notifications
 * Example cron: */5 * * * * php /path/to/process_notification_queue.php
 */

require_once __DIR__ . '/../classes/NotificationService.php';

// Create DB connection (assumes env vars or global $db available)
$dsn = getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=barron;charset=utf8mb4';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $notif = new NotificationService($db);
    
    $processed = $notif->processQueue(50);
    
    echo "[" . date('Y-m-d H:i:s') . "] Processed {$processed} notifications.\n";
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
