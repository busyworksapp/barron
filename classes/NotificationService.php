<?php
/**
 * Notification Service
 * Handles creation, delivery, and management of notifications.
 * Supports database-backed queue (can be upgraded to Redis/pubsub later).
 * Email/SMS delivery via configurable providers.
 */
class NotificationService
{
    protected $db;
    
    // Notification types
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    
    // Delivery channels
    const CHANNEL_IN_APP = 'in_app';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    
    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? $GLOBALS['db'] ?? $this->createDbConnection();
    }
    
    protected function createDbConnection(): PDO
    {
        $dsn = getenv('DB_DSN') ?: null;
        $user = getenv('DB_USER') ?: null;
        $pass = getenv('DB_PASS') ?: null;
        if (!$dsn) throw new Exception('DB connection not available');
        return new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    
    /**
     * Create a notification
     * @param int $userId Target user ID
     * @param string $title Notification title
     * @param string $message Notification body
     * @param string $type Type: info|success|warning|error
     * @param array $channels Delivery channels: ['in_app', 'email', 'sms']
     * @param array $data Optional metadata (JSON)
     * @return int Notification ID
     */
    public function create(int $userId, string $title, string $message, string $type = self::TYPE_INFO, array $channels = [self::CHANNEL_IN_APP], array $data = []): int
    {
        $stmt = $this->db->prepare('INSERT INTO notifications (user_id, title, message, type, channels, data, is_read, created_at) VALUES (:user_id, :title, :message, :type, :channels, :data, 0, NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type,
            ':channels' => json_encode($channels),
            ':data' => json_encode($data)
        ]);
        $notificationId = (int)$this->db->lastInsertId();
        
        // Queue delivery for each channel
        foreach ($channels as $channel) {
            if ($channel !== self::CHANNEL_IN_APP) {
                $this->queueDelivery($notificationId, $channel);
            }
        }
        
        return $notificationId;
    }
    
    /**
     * Queue a notification for external delivery (email/SMS)
     */
    protected function queueDelivery(int $notificationId, string $channel): void
    {
        $stmt = $this->db->prepare('INSERT INTO notification_queue (notification_id, channel, status, attempts, created_at) VALUES (:nid, :channel, :status, 0, NOW())');
        $stmt->execute([
            ':nid' => $notificationId,
            ':channel' => $channel,
            ':status' => 'pending'
        ]);
    }
    
    /**
     * Get unread notifications for a user
     */
    public function getUnread(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all notifications for a user (paginated)
     */
    public function getAll(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark notification as read
     */
    public function markRead(int $notificationId): bool
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id');
        return $stmt->execute([':id' => $notificationId]);
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :uid AND is_read = 0');
        return $stmt->execute([':uid' => $userId]);
    }
    
    /**
     * Get count of unread notifications
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :uid AND is_read = 0');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Process pending notification queue (email/SMS delivery)
     * Call this from a cron job or background worker
     */
    public function processQueue(int $batchSize = 10): int
    {
        $stmt = $this->db->prepare('SELECT nq.*, n.user_id, n.title, n.message, n.type FROM notification_queue nq JOIN notifications n ON nq.notification_id = n.id WHERE nq.status = :status AND nq.attempts < 3 ORDER BY nq.created_at LIMIT :limit');
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processed = 0;
        foreach ($items as $item) {
            $success = false;
            
            if ($item['channel'] === self::CHANNEL_EMAIL) {
                $success = $this->sendEmail($item);
            } elseif ($item['channel'] === self::CHANNEL_SMS) {
                $success = $this->sendSMS($item);
            }
            
            if ($success) {
                $this->updateQueueStatus($item['id'], 'sent');
                $processed++;
            } else {
                $this->incrementQueueAttempts($item['id']);
            }
        }
        
        return $processed;
    }
    
    protected function sendEmail(array $item): bool
    {
        // Placeholder: integrate with email provider (PHPMailer, SendGrid, AWS SES, etc.)
        // For now, log to file
        $userStmt = $this->db->prepare('SELECT email FROM users WHERE id = :id');
        $userStmt->execute([':id' => $item['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['email'])) return false;
        
        $logEntry = sprintf("[%s] Email to %s: %s - %s\n", date('Y-m-d H:i:s'), $user['email'], $item['title'], $item['message']);
        file_put_contents(__DIR__ . '/../logs/email_notifications.log', $logEntry, FILE_APPEND);
        
        // TODO: Replace with actual email sending logic
        return true;
    }
    
    protected function sendSMS(array $item): bool
    {
        // Placeholder: integrate with SMS provider (Twilio, AWS SNS, etc.)
        $userStmt = $this->db->prepare('SELECT phone FROM users WHERE id = :id');
        $userStmt->execute([':id' => $item['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['phone'])) return false;
        
        $logEntry = sprintf("[%s] SMS to %s: %s\n", date('Y-m-d H:i:s'), $user['phone'], $item['message']);
        file_put_contents(__DIR__ . '/../logs/sms_notifications.log', $logEntry, FILE_APPEND);
        
        // TODO: Replace with actual SMS sending logic
        return true;
    }
    
    protected function updateQueueStatus(int $queueId, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE notification_queue SET status = :status, processed_at = NOW() WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $queueId]);
    }
    
    protected function incrementQueueAttempts(int $queueId): void
    {
        $stmt = $this->db->prepare('UPDATE notification_queue SET attempts = attempts + 1 WHERE id = :id');
        $stmt->execute([':id' => $queueId]);
    }
    
    /**
     * Notification templates for common events
     */
    public function notifyDefectCreated(int $userId, string $defectNumber): int
    {
        return $this->create($userId, 'Defect Reported', "Defect {$defectNumber} has been created and requires attention.", self::TYPE_WARNING, [self::CHANNEL_IN_APP, self::CHANNEL_EMAIL], ['defect_number' => $defectNumber]);
    }
    
    public function notifyReplacementApproved(int $userId, string $ticketNumber): int
    {
        return $this->create($userId, 'Replacement Approved', "Replacement ticket {$ticketNumber} has been approved.", self::TYPE_SUCCESS, [self::CHANNEL_IN_APP, self::CHANNEL_EMAIL], ['ticket_number' => $ticketNumber]);
    }
    
    public function notifyJobStarted(int $userId, string $jobNumber): int
    {
        return $this->create($userId, 'Job Started', "Job {$jobNumber} has been started.", self::TYPE_INFO, [self::CHANNEL_IN_APP], ['job_number' => $jobNumber]);
    }
    
    public function notifyJobCompleted(int $userId, string $jobNumber): int
    {
        return $this->create($userId, 'Job Completed', "Job {$jobNumber} has been completed.", self::TYPE_SUCCESS, [self::CHANNEL_IN_APP], ['job_number' => $jobNumber]);
    }
}
