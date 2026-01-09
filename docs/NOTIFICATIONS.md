# Notification System Integration Guide

## Overview
The notification system provides in-app, email, and SMS delivery for events across modules.

## Architecture
- **NotificationService**: Core class for creating and managing notifications
- **Database Queue**: Pending email/SMS stored in `notification_queue` table
- **Background Worker**: `scripts/process_notification_queue.php` (run via cron)
- **Frontend Badge**: `assets/js/notification-badge.js` (auto-polling unread count)
- **Notification Center**: `pages/notifications/center.php` (full notification list)

## Schema
Run `sql/notifications_schema.sql` to create:
- `notifications` table (user notifications with read status)
- `notification_queue` table (pending delivery for email/SMS)

## Integration Examples

### Defects Module
When a defect is created, notify the manager:

```php
require_once __DIR__ . '/../classes/NotificationService.php';
$notif = new NotificationService($db);

// After creating a defect
$managerId = 5; // Get from department or user role
$notif->notifyDefectCreated($managerId, $defectNumber);
```

### Replacement Tickets
When a replacement is approved, notify the planner:

```php
$planner = 3; // Get planner user ID
$notif->notifyReplacementApproved($planner, $ticketNumber);
```

### Job Workflow
When operator starts a job, notify the planner:

```php
$notif->notifyJobStarted($plannerId, $jobNumber);
```

When a job completes, notify the manager:

```php
$notif->notifyJobCompleted($managerId, $jobNumber);
```

### Custom Notifications
For custom events:

```php
$notif->create(
    $userId,
    'Custom Event',
    'Your custom message here',
    NotificationService::TYPE_WARNING,
    [NotificationService::CHANNEL_IN_APP, NotificationService::CHANNEL_EMAIL],
    ['custom_data' => 'value']
);
```

## Email/SMS Delivery
1. **Logs**: By default, email/SMS are logged to `logs/email_notifications.log` and `logs/sms_notifications.log`
2. **Integration**: Edit `NotificationService::sendEmail()` and `sendSMS()` to use:
   - Email: PHPMailer, SendGrid, AWS SES, Mailgun
   - SMS: Twilio, AWS SNS, Nexmo

Example SendGrid integration (replace in `sendEmail()`):

```php
use SendGrid\Mail\Mail;

$email = new Mail();
$email->setFrom("noreply@barron.com", "Barron System");
$email->setSubject($item['title']);
$email->addTo($user['email'], $user['full_name']);
$email->addContent("text/plain", $item['message']);

$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
$response = $sendgrid->send($email);

return $response->statusCode() == 202;
```

## Background Processing
Add to cron (Railway/Linux) or Task Scheduler (Windows):

```bash
# Every 5 minutes
*/5 * * * * php /path/to/scripts/process_notification_queue.php >> /path/to/logs/notification_worker.log 2>&1
```

Railway: Use a scheduled job or separate worker process.

## Frontend Integration
Include the notification badge in your layout header:

```html
<div id="notificationBadge"></div>
<script src="/assets/js/notification-badge.js"></script>
```

The badge auto-polls every 30 seconds and shows a dropdown preview.

## API Endpoints
- `GET /api/notifications/notifications.php` - Get all notifications
- `GET /api/notifications/notifications.php?unread=1` - Get unread only
- `GET /api/notifications/notifications.php?unread_count=1` - Get unread count
- `POST /api/notifications/notifications.php` with `{mark_read: true, id: X}` - Mark as read
- `POST /api/notifications/notifications.php` with `{mark_all_read: true}` - Mark all as read

## Testing
1. Create a test notification:
   ```php
   $notif = new NotificationService($db);
   $notif->create($_SESSION['user_id'], 'Test', 'This is a test', 'info', ['in_app', 'email']);
   ```
2. Check notification center: `/pages/notifications/center.php`
3. Check badge updates (should show count)
4. Run queue processor: `php scripts/process_notification_queue.php`
5. Check logs: `logs/email_notifications.log`

## Redis Upgrade (Optional)
To use Redis for real-time pub/sub:

1. Install Redis and phpredis extension
2. Replace `create()` method to publish:
   ```php
   $redis = new Redis();
   $redis->connect('127.0.0.1', 6379);
   $redis->publish('notifications', json_encode(['user_id' => $userId, 'notification_id' => $notificationId]));
   ```
3. Add WebSocket server to subscribe and push to clients

## Security Notes
- All endpoints check `$_SESSION['user_id']`
- Users can only see their own notifications
- Admin endpoints (if added) should use RBAC checks
