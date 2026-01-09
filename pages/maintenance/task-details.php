<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/MaintenanceManager.php';
$maint = new MaintenanceManager($GLOBALS['db'] ?? null);
$details = $maint->getTaskDetails((int)$_GET['id']);

if (!$details) {
    die('Task not found');
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Details - <?=htmlspecialchars($details['task_number'])?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 24px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .badge { padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block; }
        .badge.scheduled { background: #3498db; color: white; }
        .badge.in_progress { background: #f39c12; color: white; }
        .badge.completed { background: #27ae60; color: white; }
        .badge.overdue { background: #e74c3c; color: white; }
        .field { margin: 16px 0; }
        .field label { font-weight: bold; display: block; margin-bottom: 4px; color: #555; }
        .field .value { padding: 8px; background: #f9f9f9; border-radius: 4px; }
        .logs { margin-top: 24px; }
        .log-entry { padding: 12px; background: #ecf0f1; border-radius: 4px; margin: 8px 0; }
        .log-entry .meta { font-size: 12px; color: #7f8c8d; margin-top: 4px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 8px; }
        .btn-success { background: #27ae60; }
        .btn-warning { background: #f39c12; }
        .log-form { margin-top: 16px; padding: 16px; background: #f9f9f9; border-radius: 8px; }
        .log-form textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; }
        .log-form input { width: 150px; padding: 8px; margin: 8px 8px 8px 0; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><?=htmlspecialchars($details['task_number'])?></h1>
            <span class="badge <?=htmlspecialchars($details['status'])?>"><?=ucfirst(str_replace('_', ' ', $details['status']))?></span>
        </div>
        <a href="/pages/maintenance/dashboard.php" class="btn">Back</a>
    </div>
    
    <div class="field">
        <label>Machine</label>
        <div class="value"><?=htmlspecialchars($details['machine_code'])?> - <?=htmlspecialchars($details['machine_name'])?></div>
    </div>
    
    <div class="field">
        <label>Title</label>
        <div class="value"><?=htmlspecialchars($details['title'])?></div>
    </div>
    
    <div class="field">
        <label>Description</label>
        <div class="value"><?=nl2br(htmlspecialchars($details['description']))?></div>
    </div>
    
    <div class="field">
        <label>Type / Priority</label>
        <div class="value"><?=ucfirst($details['type'])?> / <?=ucfirst($details['priority'])?></div>
    </div>
    
    <div class="field">
        <label>Scheduled Date</label>
        <div class="value"><?=htmlspecialchars($details['scheduled_date'])?></div>
    </div>
    
    <div class="field">
        <label>Estimated Hours</label>
        <div class="value"><?=$details['estimated_hours'] ?? 'N/A'?></div>
    </div>
    
    <?php if ($details['status'] === 'scheduled'): ?>
    <div style="margin: 24px 0;">
        <button class="btn btn-warning" onclick="updateStatus('in_progress')">Start Task</button>
    </div>
    <?php elseif ($details['status'] === 'in_progress'): ?>
    <div style="margin: 24px 0;">
        <button class="btn btn-success" onclick="updateStatus('completed')">Mark Complete</button>
    </div>
    <?php endif; ?>
    
    <div class="logs">
        <h3>Activity Logs</h3>
        <?php if (empty($details['logs'])): ?>
            <p>No logs yet</p>
        <?php else: ?>
            <?php foreach ($details['logs'] as $log): ?>
                <div class="log-entry">
                    <div><?=nl2br(htmlspecialchars($log['notes']))?></div>
                    <?php if ($log['hours_spent']): ?>
                        <div><strong>Hours spent:</strong> <?=$log['hours_spent']?></div>
                    <?php endif; ?>
                    <div class="meta">Logged at <?=htmlspecialchars($log['created_at'])?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="log-form">
            <h4>Add Log Entry</h4>
            <textarea id="logNotes" placeholder="Enter notes..."></textarea>
            <input id="logHours" type="number" step="0.5" min="0" placeholder="Hours spent">
            <button class="btn" onclick="addLog()">Add Log</button>
        </div>
    </div>
</div>

<script>
async function updateStatus(status) {
    const res = await fetch('/api/maintenance/tasks.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            update_status: true,
            id: <?=$details['id']?>,
            status: status
        })
    });
    
    const json = await res.json();
    if (json.success) {
        alert('Status updated to ' + status);
        location.reload();
    } else {
        alert('Failed: ' + (json.error || ''));
    }
}

async function addLog() {
    const notes = document.getElementById('logNotes').value;
    const hours = document.getElementById('logHours').value;
    
    if (!notes.trim()) {
        alert('Please enter notes');
        return;
    }
    
    const res = await fetch('/api/maintenance/tasks.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            log_activity: true,
            task_id: <?=$details['id']?>,
            notes: notes,
            hours_spent: hours || null
        })
    });
    
    const json = await res.json();
    if (json.success) {
        alert('Log added');
        location.reload();
    } else {
        alert('Failed: ' + (json.error || ''));
    }
}
</script>
</body>
</html>
