<?php
/**
 * NCR (Non-Conformance Report) & SOP Management
 * Handles NCR creation, approval workflows, SOP document attachments
 */
class NCRManager
{
    protected $db;
    
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CLOSED = 'closed';
    
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
     * Create a new NCR
     */
    public function create(array $data): int
    {
        $ncrNumber = $this->generateNCRNumber();
        
        $stmt = $this->db->prepare('INSERT INTO ncrs (ncr_number, title, description, reported_by, department_id, severity, status, related_job_id, created_at) VALUES (:ncr_number, :title, :description, :reported_by, :department_id, :severity, :status, :related_job_id, NOW())');
        $stmt->execute([
            ':ncr_number' => $ncrNumber,
            ':title' => $data['title'] ?? '',
            ':description' => $data['description'] ?? '',
            ':reported_by' => $data['reported_by'] ?? null,
            ':department_id' => $data['department_id'] ?? null,
            ':severity' => $data['severity'] ?? 'medium',
            ':status' => $data['status'] ?? self::STATUS_DRAFT,
            ':related_job_id' => $data['related_job_id'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Generate NCR number (NCR-YYYYMMDD-####)
     */
    protected function generateNCRNumber(): string
    {
        $date = date('Ymd');
        $prefix = "NCR-{$date}-";
        
        $stmt = $this->db->prepare('SELECT ncr_number FROM ncrs WHERE ncr_number LIKE :prefix ORDER BY ncr_number DESC LIMIT 1');
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $lastNum = (int)substr($last['ncr_number'], -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get NCRs (paginated, with filters)
     */
    public function getNCRs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['department_id'])) {
            $where[] = 'department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['severity'])) {
            $where[] = 'severity = :severity';
            $params[':severity'] = $filters['severity'];
        }
        
        $sql = 'SELECT * FROM ncrs WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get NCR details
     */
    public function getNCRDetails(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ncrs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ncr = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ncr) return null;
        
        // Get attachments (SOPs)
        $stmt = $this->db->prepare('SELECT * FROM ncr_attachments WHERE ncr_id = :ncr_id');
        $stmt->execute([':ncr_id' => $id]);
        $ncr['attachments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $ncr;
    }
    
    /**
     * Update NCR
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE ncrs SET title = :title, description = :description, severity = :severity, department_id = :department_id, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            ':title' => $data['title'] ?? '',
            ':description' => $data['description'] ?? '',
            ':severity' => $data['severity'] ?? 'medium',
            ':department_id' => $data['department_id'] ?? null,
            ':id' => $id
        ]);
    }
    
    /**
     * Update NCR status (with optional notes)
     */
    public function updateStatus(int $id, string $status, ?string $notes = null, ?int $reviewedBy = null): bool
    {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare('UPDATE ncrs SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(), review_notes = :notes, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':status' => $status,
                ':reviewed_by' => $reviewedBy,
                ':notes' => $notes,
                ':id' => $id
            ]);
            
            // Log activity
            $this->logActivity($id, "Status changed to {$status}", $reviewedBy);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Attach SOP document to NCR
     */
    public function attachSOP(int $ncrId, string $filename, string $filepath, ?string $description = null, ?int $uploadedBy = null): int
    {
        $stmt = $this->db->prepare('INSERT INTO ncr_attachments (ncr_id, filename, filepath, description, uploaded_by, uploaded_at) VALUES (:ncr_id, :filename, :filepath, :description, :uploaded_by, NOW())');
        $stmt->execute([
            ':ncr_id' => $ncrId,
            ':filename' => $filename,
            ':filepath' => $filepath,
            ':description' => $description,
            ':uploaded_by' => $uploadedBy
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Get attachments for an NCR
     */
    public function getAttachments(int $ncrId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ncr_attachments WHERE ncr_id = :ncr_id ORDER BY uploaded_at DESC');
        $stmt->execute([':ncr_id' => $ncrId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log activity for NCR
     */
    protected function logActivity(int $ncrId, string $action, ?int $userId = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO activity_log (entity_type, entity_id, action, user_id, created_at) VALUES (:entity_type, :entity_id, :action, :user_id, NOW())');
        $stmt->execute([
            ':entity_type' => 'ncr',
            ':entity_id' => $ncrId,
            ':action' => $action,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total NCRs
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM ncrs');
        $stats['total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // By status
        $stmt = $this->db->query('SELECT status, COUNT(*) as count FROM ncrs GROUP BY status');
        $stats['by_status'] = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }
        
        // By severity
        $stmt = $this->db->query('SELECT severity, COUNT(*) as count FROM ncrs GROUP BY severity');
        $stats['by_severity'] = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_severity'][$row['severity']] = (int)$row['count'];
        }
        
        return $stats;
    }
}
