<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/NCRManager.php';
require_once __DIR__ . '/../../classes/MasterData.php';

$md = new MasterData($GLOBALS['db'] ?? null);
$departments = $md->getDepartments(100, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create NCR</title>
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
    <h1>Create Non-Conformance Report</h1>
    
    <form id="ncrForm">
        <label>Title *</label>
        <input name="title" required>
        
        <label>Description *</label>
        <textarea name="description" required></textarea>
        
        <label>Department</label>
        <select name="department_id">
            <option value="">Select Department</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?=$d['id']?>"><?=htmlspecialchars($d['name'])?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Severity *</label>
        <select name="severity" required>
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
        </select>
        
        <label>Related Job ID (optional)</label>
        <input name="related_job_id" type="number" placeholder="Enter job ID if related">
        
        <label>Status</label>
        <select name="status">
            <option value="draft">Draft</option>
            <option value="submitted" selected>Submit for Review</option>
        </select>
        
        <button type="submit" class="btn">Create NCR</button>
        <a href="/pages/ncr/dashboard.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; margin-left: 8px;">Cancel</a>
    </form>
</div>

<script>
document.getElementById('ncrForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    
    const res = await fetch('/api/ncr/ncrs.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const json = await res.json();
    if (json.success) {
        alert('NCR created: ' + json.id);
        location.href = '/pages/ncr/dashboard.php';
    } else {
        alert('Failed: ' + (json.error || ''));
    }
});
</script>
</body>
</html>
