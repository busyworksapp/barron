<?php
/**
 * Maintenance Manager
 * Handles maintenance scheduling, machine records, task management, and worker assignments
 */
class MaintenanceManager
{
    protected $db;
    
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    
    const TYPE_PREVENTIVE = 'preventive';
    const TYPE_CORRECTIVE = 'corrective';
    const TYPE_INSPECTION = 'inspection';
    
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
    
    // Machine Management
    
    public function createMachine(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO machines (code, name, description, department_id, location, purchase_date, warranty_expiry, status, created_at) VALUES (:code, :name, :description, :department_id, :location, :purchase_date, :warranty_expiry, :status, NOW())');
        $stmt->execute([
            ':code' => $data['code'] ?? '',
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':department_id' => $data['department_id'] ?? null,
            ':location' => $data['location'] ?? '',
            ':purchase_date' => $data['purchase_date'] ?? null,
            ':warranty_expiry' => $data['warranty_expiry'] ?? null,
            ':status' => $data['status'] ?? 'active'
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function getMachines(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM machines ORDER BY name LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMachine(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM machines WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
    
    public function updateMachine(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE machines SET code = :code, name = :name, description = :description, department_id = :department_id, location = :location, status = :status WHERE id = :id');
        return $stmt->execute([
            ':code' => $data['code'] ?? '',
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':department_id' => $data['department_id'] ?? null,
            ':location' => $data['location'] ?? '',
            ':status' => $data['status'] ?? 'active',
            ':id' => $id
        ]);
    }
    
    // Maintenance Task Management
    
    public function createTask(array $data): int
    {
        $taskNumber = $this->generateTaskNumber();
        
        $stmt = $this->db->prepare('INSERT INTO maintenance_tasks (task_number, machine_id, title, description, type, priority, scheduled_date, estimated_hours, assigned_to, status, created_by, created_at) VALUES (:task_number, :machine_id, :title, :description, :type, :priority, :scheduled_date, :estimated_hours, :assigned_to, :status, :created_by, NOW())');
        $stmt->execute([
            ':task_number' => $taskNumber,
            ':machine_id' => $data['machine_id'] ?? null,
            ':title' => $data['title'] ?? '',
            ':description' => $data['description'] ?? '',
            ':type' => $data['type'] ?? self::TYPE_PREVENTIVE,
            ':priority' => $data['priority'] ?? 'medium',
            ':scheduled_date' => $data['scheduled_date'] ?? null,
            ':estimated_hours' => $data['estimated_hours'] ?? null,
            ':assigned_to' => $data['assigned_to'] ?? null,
            ':status' => $data['status'] ?? self::STATUS_SCHEDULED,
            ':created_by' => $data['created_by'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    protected function generateTaskNumber(): string
    {
        $date = date('Ymd');
        $prefix = "MNT-{$date}-";
        
        $stmt = $this->db->prepare('SELECT task_number FROM maintenance_tasks WHERE task_number LIKE :prefix ORDER BY task_number DESC LIMIT 1');
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $lastNum = (int)substr($last['task_number'], -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    public function getTasks(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['machine_id'])) {
            $where[] = 'machine_id = :machine_id';
            $params[':machine_id'] = $filters['machine_id'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = :assigned_to';
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['overdue'])) {
            $where[] = 'scheduled_date < CURDATE() AND status NOT IN (:completed, :cancelled)';
            $params[':completed'] = self::STATUS_COMPLETED;
            $params[':cancelled'] = self::STATUS_CANCELLED;
        }
        
        $sql = 'SELECT mt.*, m.name as machine_name, m.code as machine_code FROM maintenance_tasks mt LEFT JOIN machines m ON mt.machine_id = m.id WHERE ' . implode(' AND ', $where) . ' ORDER BY mt.scheduled_date DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTaskDetails(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT mt.*, m.name as machine_name, m.code as machine_code, m.location FROM maintenance_tasks mt LEFT JOIN machines m ON mt.machine_id = m.id WHERE mt.id = :id');
        $stmt->execute([':id' => $id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) return null;
        
        // Get maintenance logs
        $stmt = $this->db->prepare('SELECT * FROM maintenance_logs WHERE task_id = :task_id ORDER BY created_at DESC');
        $stmt->execute([':task_id' => $id]);
        $task['logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $task;
    }
    
    public function updateTaskStatus(int $id, string $status, ?int $userId = null): bool
    {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare('UPDATE maintenance_tasks SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':status' => $status, ':id' => $id]);
            
            if ($status === self::STATUS_IN_PROGRESS && !$this->hasLog($id, 'started')) {
                $this->logActivity($id, 'Task started', $userId);
            } elseif ($status === self::STATUS_COMPLETED) {
                $stmt = $this->db->prepare('UPDATE maintenance_tasks SET completed_date = NOW() WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $this->logActivity($id, 'Task completed', $userId);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function logActivity(int $taskId, string $notes, ?int $userId = null, ?float $hoursSpent = null): int
    {
        $stmt = $this->db->prepare('INSERT INTO maintenance_logs (task_id, user_id, notes, hours_spent, created_at) VALUES (:task_id, :user_id, :notes, :hours_spent, NOW())');
        $stmt->execute([
            ':task_id' => $taskId,
            ':user_id' => $userId,
            ':notes' => $notes,
            ':hours_spent' => $hoursSpent
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    protected function hasLog(int $taskId, string $keyword): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM maintenance_logs WHERE task_id = :task_id AND notes LIKE :keyword');
        $stmt->execute([':task_id' => $taskId, ':keyword' => "%{$keyword}%"]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['count'] > 0;
    }
    
    public function getCalendar(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('SELECT mt.*, m.name as machine_name, m.code as machine_code FROM maintenance_tasks mt LEFT JOIN machines m ON mt.machine_id = m.id WHERE mt.scheduled_date BETWEEN :start AND :end ORDER BY mt.scheduled_date');
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total machines
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM machines WHERE status = "active"');
        $stats['total_machines'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total tasks
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM maintenance_tasks');
        $stats['total_tasks'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tasks by status
        $stmt = $this->db->query('SELECT status, COUNT(*) as count FROM maintenance_tasks GROUP BY status');
        $stats['by_status'] = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }
        
        // Overdue tasks
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM maintenance_tasks WHERE scheduled_date < CURDATE() AND status NOT IN (:completed, :cancelled)');
        $stmt->execute([':completed' => self::STATUS_COMPLETED, ':cancelled' => self::STATUS_CANCELLED]);
        $stats['overdue'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
}
