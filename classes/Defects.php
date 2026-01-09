<?php
/**
 * Barron Production Management System
 * Defects Management
 */

class Defects {
    private $conn;
    private $database;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * Get defects with filters and pagination
     */
    public function getDefects($filters = []) {
        try {
            $where_conditions = ['1=1'];
            $params = [];
            
            // Search filter
            if (!empty($filters['search'])) {
                $where_conditions[] = "(d.defect_number LIKE :search OR d.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Job filter
            if (!empty($filters['job_id'])) {
                $where_conditions[] = "d.job_id = :job_id";
                $params[':job_id'] = $filters['job_id'];
            }
            
            // Order filter
            if (!empty($filters['order_id'])) {
                $where_conditions[] = "j.order_id = :order_id";
                $params[':order_id'] = $filters['order_id'];
            }
            
            // Status filter
            if (!empty($filters['status'])) {
                $where_conditions[] = "d.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Severity filter
            if (!empty($filters['severity'])) {
                $where_conditions[] = "d.severity = :severity";
                $params[':severity'] = $filters['severity'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(d.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(d.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Build WHERE clause
            $where_clause = implode(' AND ', $where_conditions);
            
            // Count total records
            $count_query = "SELECT COUNT(*) as total
                           FROM defects d
                           INNER JOIN jobs j ON d.job_id = j.id
                           INNER JOIN orders o ON j.order_id = o.id
                           WHERE {$where_clause}";
            
            $count_stmt = $this->conn->prepare($count_query);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];
            
            // Pagination
            $page = $filters['page'] ?? 1;
            $per_page = $filters['per_page'] ?? 20;
            $offset = ($page - 1) * $per_page;
            
            // Get defects
            $query = "SELECT 
                        d.*,
                        j.job_number,
                        j.order_id,
                        o.order_number,
                        o.customer_name,
                        p.product_name,
                        dept.department_name,
                        u.first_name as reported_by_name,
                        u.last_name as reported_by_lastname
                      FROM defects d
                      INNER JOIN jobs j ON d.job_id = j.id
                      INNER JOIN orders o ON j.order_id = o.id
                      LEFT JOIN products p ON d.product_id = p.id
                      LEFT JOIN departments dept ON j.department_id = dept.id
                      LEFT JOIN users u ON d.reported_by = u.id
                      WHERE {$where_clause}
                      ORDER BY d.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind all params
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $defects = $stmt->fetchAll();
            
            return [
                'data' => $defects,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $per_page)
                ]
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error fetching defects: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single defect details
     */
    public function getDefectDetails($id) {
        try {
            $query = "SELECT 
                        d.*,
                        j.job_number,
                        j.order_id,
                        j.department_id,
                        o.order_number,
                        o.customer_name,
                        p.product_name,
                        p.product_code,
                        dept.department_name,
                        u.first_name as reported_by_name,
                        u.last_name as reported_by_lastname,
                        u.email as reported_by_email
                      FROM defects d
                      INNER JOIN jobs j ON d.job_id = j.id
                      INNER JOIN orders o ON j.order_id = o.id
                      LEFT JOIN products p ON d.product_id = p.id
                      LEFT JOIN departments dept ON j.department_id = dept.id
                      LEFT JOIN users u ON d.reported_by = u.id
                      WHERE d.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching defect details: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new defect
     */
    public function create($data) {
        try {
            $this->conn->beginTransaction();
            
            // Generate defect number
            $defect_number = $this->generateDefectNumber();
            
            $query = "INSERT INTO defects 
                     (defect_number, job_id, product_id, quantity, severity, 
                      description, root_cause, corrective_action, status, 
                      requires_replacement, reported_by)
                     VALUES 
                     (:defect_number, :job_id, :product_id, :quantity, :severity,
                      :description, :root_cause, :corrective_action, :status,
                      :requires_replacement, :reported_by)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':defect_number' => $defect_number,
                ':job_id' => $data['job_id'],
                ':product_id' => $data['product_id'] ?? null,
                ':quantity' => $data['quantity'],
                ':severity' => $data['severity'] ?? 'medium',
                ':description' => $data['description'],
                ':root_cause' => $data['root_cause'] ?? null,
                ':corrective_action' => $data['corrective_action'] ?? null,
                ':status' => 'open',
                ':requires_replacement' => $data['requires_replacement'] ?? 0,
                ':reported_by' => $_SESSION['user_id'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create defect');
            }
            
            $defect_id = $this->conn->lastInsertId();
            
            // If requires replacement, create replacement ticket
            if (!empty($data['requires_replacement'])) {
                require_once __DIR__ . '/ReplacementTicket.php';
                $tickets = new ReplacementTicket();
                $tickets->createFromDefect($defect_id, $data);
            }
            
            // Log activity
            logActivity('insert', 'defects', $defect_id, null, $data);
            
            $this->conn->commit();
            
            return $defect_id;
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Update defect
     */
    public function update($id, $data) {
        try {
            // Get current data for logging
            $current = $this->getDefectDetails($id);
            
            if (!$current) {
                throw new Exception('Defect not found');
            }
            
            // Build update query dynamically
            $allowed_fields = ['quantity', 'severity', 'description', 'root_cause', 
                              'corrective_action', 'status'];
            
            $update_fields = [];
            $params = [':id' => $id];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $update_fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }
            
            if (empty($update_fields)) {
                return true; // Nothing to update
            }
            
            $update_fields[] = "updated_at = CURRENT_TIMESTAMP";
            
            $query = "UPDATE defects SET " . implode(', ', $update_fields) . " WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Log activity
                logActivity('update', 'defects', $id, $current, $data);
            }
            
            return $result;
            
        } catch (Exception $e) {
            throw new Exception('Error updating defect: ' . $e->getMessage());
        }
    }
    
    /**
     * Update defect status
     */
    public function updateStatus($id, $status, $notes = null) {
        try {
            $query = "UPDATE defects 
                     SET status = :status,
                         resolution_notes = :notes,
                         resolved_at = CASE WHEN :status IN ('resolved', 'closed') THEN CURRENT_TIMESTAMP ELSE resolved_at END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id' => $id,
                ':status' => $status,
                ':notes' => $notes
            ]);
            
            if ($result) {
                // Log activity
                logActivity('update', 'defects', $id, ['status' => $status, 'notes' => $notes], null);
            }
            
            return $result;
            
        } catch (Exception $e) {
            throw new Exception('Error updating defect status: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique defect number
     */
    private function generateDefectNumber() {
        $prefix = 'DEF';
        $date = date('Ymd');
        
        // Get last defect number for today
        $query = "SELECT defect_number 
                 FROM defects 
                 WHERE defect_number LIKE :pattern 
                 ORDER BY defect_number DESC 
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':pattern' => $prefix . '-' . $date . '-%']);
        
        $last = $stmt->fetch();
        
        if ($last) {
            // Extract sequence number and increment
            $parts = explode('-', $last['defect_number']);
            $seq = intval($parts[2] ?? 0) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }
    
    /**
     * Get defect statistics
     */
    public function getStatistics($date_from = null, $date_to = null) {
        try {
            $date_from = $date_from ?? date('Y-m-01'); // First day of current month
            $date_to = $date_to ?? date('Y-m-t'); // Last day of current month
            
            $query = "SELECT 
                        COUNT(*) as total_defects,
                        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_defects,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_defects,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_defects,
                        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_defects,
                        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_defects,
                        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_defects,
                        SUM(CASE WHEN requires_replacement = 1 THEN 1 ELSE 0 END) as requires_replacement,
                        SUM(quantity) as total_quantity_affected
                     FROM defects
                     WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':date_from' => $date_from,
                ':date_to' => $date_to
            ]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Get defects by department
     */
    public function getByDepartment($date_from = null, $date_to = null) {
        try {
            $date_from = $date_from ?? date('Y-m-01');
            $date_to = $date_to ?? date('Y-m-t');
            
            $query = "SELECT 
                        dept.id,
                        dept.department_name,
                        COUNT(d.id) as defect_count,
                        SUM(d.quantity) as total_quantity,
                        SUM(CASE WHEN d.severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
                        SUM(CASE WHEN d.status = 'open' THEN 1 ELSE 0 END) as open_count
                     FROM defects d
                     INNER JOIN jobs j ON d.job_id = j.id
                     INNER JOIN departments dept ON j.department_id = dept.id
                     WHERE DATE(d.created_at) BETWEEN :date_from AND :date_to
                     GROUP BY dept.id
                     ORDER BY defect_count DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':date_from' => $date_from,
                ':date_to' => $date_to
            ]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching department defects: ' . $e->getMessage());
        }
    }
    
    /**
     * Get defects trend over time
     */
    public function getTrend($period = 'week') {
        try {
            $group_by = $period === 'week' ? 'YEARWEEK(created_at)' : 'DATE(created_at)';
            $date_format = $period === 'week' ? "CONCAT(YEAR(created_at), '-W', WEEK(created_at))" : "DATE(created_at)";
            
            $query = "SELECT 
                        {$date_format} as period,
                        COUNT(*) as defect_count,
                        SUM(quantity) as quantity_affected,
                        SUM(CASE WHEN severity IN ('critical', 'high') THEN 1 ELSE 0 END) as high_severity_count
                     FROM defects
                     WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                     GROUP BY {$group_by}
                     ORDER BY period";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching defect trend: ' . $e->getMessage());
        }
    }
}
