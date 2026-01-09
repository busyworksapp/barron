<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/NotificationService.php';
$notif = new NotificationService($GLOBALS['db'] ?? null);
$userId = (int)$_SESSION['user_id'];
$notifications = $notif->getAll($userId, 100, 0);
$unreadCount = $notif->getUnreadCount($userId);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { margin: 0 0 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .badge { background: #e74c3c; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .notification { padding: 16px; margin: 8px 0; border-left: 4px solid #3498db; background: #ecf0f1; border-radius: 4px; position: relative; }
        .notification.unread { background: #fff; border-left-color: #e74c3c; }
        .notification.success { border-left-color: #27ae60; }
        .notification.warning { border-left-color: #f39c12; }
        .notification.error { border-left-color: #e74c3c; }
        .notification .title { font-weight: bold; margin-bottom: 4px; }
        .notification .message { color: #555; font-size: 14px; margin-bottom: 8px; }
        .notification .time { font-size: 12px; color: #999; }
        .notification .mark-read { position: absolute; top: 8px; right: 8px; font-size: 12px; color: #3498db; cursor: pointer; text-decoration: underline; }
        .empty { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Notifications <?php if ($unreadCount > 0): ?><span class="badge"><?=$unreadCount?></span><?php endif; ?></h1>
        <?php if ($unreadCount > 0): ?>
            <button class="btn" id="markAllReadBtn">Mark All as Read</button>
        <?php endif; ?>
    </div>
    
    <div id="notificationList">
        <?php if (empty($notifications)): ?>
            <div class="empty">No notifications yet</div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notification <?=$n['is_read'] ? '' : 'unread'?> <?=htmlspecialchars($n['type'])?>" data-id="<?=$n['id']?>">
                    <?php if (!$n['is_read']): ?>
                        <span class="mark-read" data-id="<?=$n['id']?>">Mark as read</span>
                    <?php endif; ?>
                    <div class="title"><?=htmlspecialchars($n['title'])?></div>
                    <div class="message"><?=htmlspecialchars($n['message'])?></div>
                    <div class="time"><?=htmlspecialchars($n['created_at'])?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('markAllReadBtn')?.addEventListener('click', async function() {
    const res = await fetch('/api/notifications/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({mark_all_read: true})
    });
    const json = await res.json();
    if (json.success) location.reload();
});

document.querySelectorAll('.mark-read').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        const res = await fetch('/api/notifications/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({mark_read: true, id: id})
        });
        const json = await res.json();
        if (json.success) {
            const notification = this.closest('.notification');
            notification.classList.remove('unread');
            this.remove();
        }
    });
});
</script>
</body>
</html>
