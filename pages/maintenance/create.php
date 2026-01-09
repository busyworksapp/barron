<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/MaintenanceManager.php';
require_once __DIR__ . '/../../classes/MasterData.php';

$maint = new MaintenanceManager($GLOBALS['db'] ?? null);
$md = new MasterData($GLOBALS['db'] ?? null);

$machines = $maint->getMachines(200, 0);
$users = $md->getUsers(200, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Maintenance Task</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 24px; border-radius: 8px; }
        h1 { margin-top: 0; }
        label { display: block; margin: 16px 0 4px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { min-height: 100px; }
        .btn { padding: 12px 24px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 16px; }
        .btn:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; }
        .btn-secondary:hover { background: #7f8c8d; }
    </style>
</head>
<body>
<div class="container">
    <h1>Create Maintenance Task</h1>
    
    <form id="taskForm">
        <label>Machine *</label>
        <select name="machine_id" required>
            <option value="">Select Machine</option>
            <?php foreach ($machines as $m): ?>
                <option value="<?=$m['id']?>"><?=htmlspecialchars($m['code'])?> - <?=htmlspecialchars($m['name'])?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Title *</label>
        <input name="title" required>
        
        <label>Description</label>
        <textarea name="description"></textarea>
        
        <label>Type *</label>
        <select name="type" required>
            <option value="preventive">Preventive</option>
            <option value="corrective">Corrective</option>
            <option value="inspection">Inspection</option>
        </select>
        
        <label>Priority *</label>
        <select name="priority" required>
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
        </select>
        
        <label>Scheduled Date *</label>
        <input name="scheduled_date" type="date" required>
        
        <label>Estimated Hours</label>
        <input name="estimated_hours" type="number" step="0.5" min="0" placeholder="e.g., 2.5">
        
        <label>Assign To</label>
        <select name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach ($users as $u): ?>
                <option value="<?=$u['id']?>"><?=htmlspecialchars($u['full_name'] ?: $u['username'])?></option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn">Create Task</button>
        <a href="/pages/maintenance/dashboard.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; margin-left: 8px;">Cancel</a>
    </form>
</div>

<script>
document.getElementById('taskForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    
    const res = await fetch('/api/maintenance/tasks.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const json = await res.json();
    if (json.success) {
        alert('Task created: ' + json.id);
        location.href = '/pages/maintenance/dashboard.php';
    } else {
        alert('Failed: ' + (json.error || ''));
    }
});
</script>
</body>
</html>
