<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/NCRManager.php';
$ncr = new NCRManager($GLOBALS['db'] ?? null);
$details = $ncr->getNCRDetails((int)$_GET['id']);

if (!$details) {
    die('NCR not found');
}

$isManager = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['manager', 'admin'], true);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NCR Details - <?=htmlspecialchars($details['ncr_number'])?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 24px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .badge { padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block; }
        .badge.draft { background: #95a5a6; color: white; }
        .badge.submitted { background: #3498db; color: white; }
        .badge.under_review { background: #f39c12; color: white; }
        .badge.approved { background: #27ae60; color: white; }
        .badge.rejected { background: #e74c3c; color: white; }
        .field { margin: 16px 0; }
        .field label { font-weight: bold; display: block; margin-bottom: 4px; color: #555; }
        .field .value { padding: 8px; background: #f9f9f9; border-radius: 4px; }
        .attachments { margin-top: 24px; }
        .attachment { padding: 12px; background: #ecf0f1; border-radius: 4px; margin: 8px 0; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 8px; }
        .btn-success { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
        .review-form { margin-top: 24px; padding: 16px; background: #f9f9f9; border-radius: 8px; }
        .review-form textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><?=htmlspecialchars($details['ncr_number'])?></h1>
            <span class="badge <?=htmlspecialchars($details['status'])?>"><?=ucfirst(str_replace('_', ' ', $details['status']))?></span>
        </div>
        <a href="/pages/ncr/dashboard.php" class="btn">Back to Dashboard</a>
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
        <label>Severity</label>
        <div class="value"><?=ucfirst($details['severity'])?></div>
    </div>
    
    <div class="field">
        <label>Created</label>
        <div class="value"><?=htmlspecialchars($details['created_at'])?></div>
    </div>
    
    <?php if ($details['review_notes']): ?>
    <div class="field">
        <label>Review Notes</label>
        <div class="value"><?=nl2br(htmlspecialchars($details['review_notes']))?></div>
    </div>
    <?php endif; ?>
    
    <div class="attachments">
        <h3>Attachments (SOPs)</h3>
        <?php if (empty($details['attachments'])): ?>
            <p>No attachments</p>
        <?php else: ?>
            <?php foreach ($details['attachments'] as $att): ?>
                <div class="attachment">
                    <strong><?=htmlspecialchars($att['filename'])?></strong>
                    <?php if ($att['description']): ?>
                        <div><?=htmlspecialchars($att['description'])?></div>
                    <?php endif; ?>
                    <a href="<?=htmlspecialchars($att['filepath'])?>" target="_blank">Download</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <form id="uploadForm" enctype="multipart/form-data" style="margin-top: 16px;">
            <input type="file" name="file" required>
            <input type="text" name="description" placeholder="Description (optional)" style="width: 300px; padding: 8px; margin: 0 8px;">
            <button type="submit" class="btn">Upload Attachment</button>
        </form>
    </div>
    
    <?php if ($isManager && in_array($details['status'], ['submitted', 'under_review'])): ?>
    <div class="review-form">
        <h3>Review Actions</h3>
        <textarea id="reviewNotes" placeholder="Enter review notes..."></textarea>
        <div style="margin-top: 12px;">
            <button class="btn btn-success" onclick="updateStatus('approved')">Approve</button>
            <button class="btn btn-danger" onclick="updateStatus('rejected')">Reject</button>
            <button class="btn" onclick="updateStatus('under_review')">Mark Under Review</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('ncr_id', <?=$details['id']?>);
    
    const res = await fetch('/api/ncr/attachments.php', {
        method: 'POST',
        body: formData
    });
    
    const json = await res.json();
    if (json.success) {
        alert('Attachment uploaded');
        location.reload();
    } else {
        alert('Failed: ' + (json.error || ''));
    }
});

async function updateStatus(status) {
    const notes = document.getElementById('reviewNotes').value;
    const res = await fetch('/api/ncr/ncrs.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            update_status: true,
            id: <?=$details['id']?>,
            status: status,
            notes: notes
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
</script>
</body>
</html>
