<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/NCRManager.php';
$ncr = new NCRManager($GLOBALS['db'] ?? null);
$ncrs = $ncr->getNCRs([], 100, 0);
$stats = $ncr->getStatistics();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NCR Dashboard</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 8px; font-size: 14px; color: #666; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .filters { background: white; padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .filters select { padding: 8px; margin-right: 8px; border: 1px solid #ddd; border-radius: 4px; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge.draft { background: #95a5a6; color: white; }
        .badge.submitted { background: #3498db; color: white; }
        .badge.under_review { background: #f39c12; color: white; }
        .badge.approved { background: #27ae60; color: white; }
        .badge.rejected { background: #e74c3c; color: white; }
        .badge.closed { background: #7f8c8d; color: white; }
        .severity-high { color: #e74c3c; font-weight: bold; }
        .severity-medium { color: #f39c12; }
        .severity-low { color: #3498db; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Non-Conformance Reports (NCR)</h1>
        <a href="/pages/ncr/create.php" class="btn">+ New NCR</a>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <h3>Total NCRs</h3>
            <div class="value"><?=$stats['total']?></div>
        </div>
        <div class="stat-card">
            <h3>Open</h3>
            <div class="value"><?=($stats['by_status']['submitted'] ?? 0) + ($stats['by_status']['under_review'] ?? 0)?></div>
        </div>
        <div class="stat-card">
            <h3>Approved</h3>
            <div class="value"><?=$stats['by_status']['approved'] ?? 0?></div>
        </div>
        <div class="stat-card">
            <h3>High Severity</h3>
            <div class="value"><?=$stats['by_severity']['high'] ?? 0?></div>
        </div>
    </div>
    
    <div class="filters">
        <select id="statusFilter">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="closed">Closed</option>
        </select>
        
        <select id="severityFilter">
            <option value="">All Severity</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>
        
        <button class="btn" onclick="applyFilters()">Apply Filters</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>NCR Number</th>
                <th>Title</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ncrs as $n): ?>
                <tr>
                    <td><?=htmlspecialchars($n['ncr_number'])?></td>
                    <td><?=htmlspecialchars($n['title'])?></td>
                    <td class="severity-<?=htmlspecialchars($n['severity'])?>"><?=ucfirst($n['severity'])?></td>
                    <td><span class="badge <?=htmlspecialchars($n['status'])?>"><?=ucfirst(str_replace('_', ' ', $n['status']))?></span></td>
                    <td><?=htmlspecialchars($n['created_at'])?></td>
                    <td><a href="/pages/ncr/details.php?id=<?=$n['id']?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const severity = document.getElementById('severityFilter').value;
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (severity) params.set('severity', severity);
    location.search = params.toString();
}
</script>
</body>
</html>
