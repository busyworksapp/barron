<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/MaintenanceManager.php';
$maint = new MaintenanceManager($GLOBALS['db'] ?? null);
$stats = $maint->getStatistics();
$tasks = $maint->getTasks([], 50, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Dashboard</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 8px; }
        .btn:hover { background: #2980b9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 8px; font-size: 14px; color: #666; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .stat-card.alert { border-left: 4px solid #e74c3c; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge.scheduled { background: #3498db; color: white; }
        .badge.in_progress { background: #f39c12; color: white; }
        .badge.completed { background: #27ae60; color: white; }
        .badge.overdue { background: #e74c3c; color: white; }
        .priority-high { color: #e74c3c; font-weight: bold; }
        .priority-medium { color: #f39c12; }
        .priority-low { color: #3498db; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Maintenance Dashboard</h1>
        <div>
            <a href="/pages/maintenance/create.php" class="btn">+ New Task</a>
            <a href="/pages/maintenance/machines.php" class="btn">Machines</a>
            <a href="/pages/maintenance/calendar.php" class="btn">Calendar</a>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <h3>Active Machines</h3>
            <div class="value"><?=$stats['total_machines']?></div>
        </div>
        <div class="stat-card">
            <h3>Total Tasks</h3>
            <div class="value"><?=$stats['total_tasks']?></div>
        </div>
        <div class="stat-card">
            <h3>Scheduled</h3>
            <div class="value"><?=$stats['by_status']['scheduled'] ?? 0?></div>
        </div>
        <div class="stat-card alert">
            <h3>Overdue</h3>
            <div class="value"><?=$stats['overdue']?></div>
        </div>
    </div>
    
    <h2>Recent Tasks</h2>
    <table>
        <thead>
            <tr>
                <th>Task Number</th>
                <th>Machine</th>
                <th>Title</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Scheduled</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?=htmlspecialchars($t['task_number'])?></td>
                    <td><?=htmlspecialchars($t['machine_code'] ?? 'N/A')?></td>
                    <td><?=htmlspecialchars($t['title'])?></td>
                    <td><?=ucfirst($t['type'])?></td>
                    <td class="priority-<?=htmlspecialchars($t['priority'])?>"><?=ucfirst($t['priority'])?></td>
                    <td><?=htmlspecialchars($t['scheduled_date'])?></td>
                    <td><span class="badge <?=htmlspecialchars($t['status'])?>"><?=ucfirst(str_replace('_', ' ', $t['status']))?></span></td>
                    <td><a href="/pages/maintenance/task-details.php?id=<?=$t['id']?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
