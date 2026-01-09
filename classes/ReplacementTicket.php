<?php
/**
 * Barron Production Management System
 * Replacement Ticket Management
 */

class ReplacementTicket {
    private $conn;
    private $database;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * Create replacement ticket from defect
     */
    public function createFromDefect($defect_id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Generate ticket number
            $ticket_number = $this->generateTicketNumber();
            
            // Get defect details
            $defect_query = "SELECT d.*, j.order_id, j.department_id 
                            FROM defects d
                            INNER JOIN jobs j ON d.job_id = j.id
                            WHERE d.id = :defect_id";
            $defect_stmt = $this->conn->prepare($defect_query);
            $defect_stmt->execute([':defect_id' => $defect_id]);
            $defect = $defect_stmt->fetch();
            
            if (!$defect) {
                throw new Exception('Defect not found');
            }
            
            // Create replacement ticket
            $query = "INSERT INTO replacement_tickets 
                     (ticket_number, defect_id, order_id, product_id, quantity_required,
                      urgency, status, requested_by, notes)
                     VALUES 
                     (:ticket_number, :defect_id, :order_id, :product_id, :quantity_required,
                      :urgency, :status, :requested_by, :notes)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':ticket_number' => $ticket_number,
                ':defect_id' => $defect_id,
                ':order_id' => $defect['order_id'],
                ':product_id' => $defect['product_id'],
                ':quantity_required' => $data['replacement_quantity'] ?? $defect['quantity'],
                ':urgency' => $data['urgency'] ?? 'normal',
                ':status' => 'pending_approval',
                ':requested_by' => $_SESSION['user_id'] ?? null,
                ':notes' => $data['replacement_notes'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create replacement ticket');
            }
            
            $ticket_id = $this->conn->lastInsertId();
            
            // Send notification to manager for approval
            $this->sendApprovalNotification($ticket_id, $defect['department_id']);
            
            // Log activity
            logActivity('insert', 'replacement_tickets', $ticket_id, null, $data);
            
            $this->conn->commit();
            
            return $ticket_id;
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Get replacement tickets with filters
     */
    public function getTickets($filters = []) {
        try {
            $where_conditions = ['1=1'];
            $params = [];
            
            // Status filter
            if (!empty($filters['status'])) {
                $where_conditions[] = "rt.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Urgency filter
            if (!empty($filters['urgency'])) {
                $where_conditions[] = "rt.urgency = :urgency";
                $params[':urgency'] = $filters['urgency'];
            }
            
            // Order filter
            if (!empty($filters['order_id'])) {
                $where_conditions[] = "rt.order_id = :order_id";
                $params[':order_id'] = $filters['order_id'];
            }
            
            // Date range
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(rt.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(rt.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Count total
            $count_query = "SELECT COUNT(*) as total
                           FROM replacement_tickets rt
                           WHERE {$where_clause}";
            
            $count_stmt = $this->conn->prepare($count_query);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];
            
            // Pagination
            $page = $filters['page'] ?? 1;
            $per_page = $filters['per_page'] ?? 20;
            $offset = ($page - 1) * $per_page;
            
            // Get tickets
            $query = "SELECT 
                        rt.*,
                        d.defect_number,
                        d.severity as defect_severity,
                        o.order_number,
                        o.customer_name,
                        p.product_name,
                        p.product_code,
                        u.first_name as requested_by_name,
                        u.last_name as requested_by_lastname,
                        approver.first_name as approved_by_name,
                        approver.last_name as approved_by_lastname
                      FROM replacement_tickets rt
                      INNER JOIN defects d ON rt.defect_id = d.id
                      INNER JOIN orders o ON rt.order_id = o.id
                      LEFT JOIN products p ON rt.product_id = p.id
                      LEFT JOIN users u ON rt.requested_by = u.id
                      LEFT JOIN users approver ON rt.approved_by = approver.id
                      WHERE {$where_clause}
                      ORDER BY rt.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $tickets = $stmt->fetchAll();
            
            return [
                'data' => $tickets,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $per_page)
                ]
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error fetching tickets: ' . $e->getMessage());
        }
    }
    
    /**
     * Get ticket details
     */
    public function getTicketDetails($id) {
        try {
            $query = "SELECT 
                        rt.*,
                        d.defect_number,
                        d.description as defect_description,
                        d.severity as defect_severity,
                        d.job_id,
                        j.job_number,
                        o.order_number,
                        o.customer_name,
                        p.product_name,
                        p.product_code,
                        u.first_name as requested_by_name,
                        u.last_name as requested_by_lastname,
                        u.email as requested_by_email,
                        approver.first_name as approved_by_name,
                        approver.last_name as approved_by_lastname
                      FROM replacement_tickets rt
                      INNER JOIN defects d ON rt.defect_id = d.id
                      INNER JOIN jobs j ON d.job_id = j.id
                      INNER JOIN orders o ON rt.order_id = o.id
                      LEFT JOIN products p ON rt.product_id = p.id
                      LEFT JOIN users u ON rt.requested_by = u.id
                      LEFT JOIN users approver ON rt.approved_by = approver.id
                      WHERE rt.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching ticket details: ' . $e->getMessage());
        }
    }
    
    /**
     * Approve replacement ticket (Manager action)
     */
    public function approve($id, $notes = null) {
        try {
            $this->conn->beginTransaction();
            
            $query = "UPDATE replacement_tickets 
                     SET status = 'approved',
                         approved_by = :approved_by,
                         approved_at = CURRENT_TIMESTAMP,
                         approval_notes = :notes,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND status = 'pending_approval'";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id' => $id,
                ':approved_by' => $_SESSION['user_id'],
                ':notes' => $notes
            ]);
            
            if (!$result || $stmt->rowCount() === 0) {
                throw new Exception('Ticket not found or already processed');
            }
            
            // Log activity
            logActivity('update', 'replacement_tickets', $id, ['status' => 'approved', 'notes' => $notes], null);
            
            // Send notification to planning team
            $this->sendPlanningNotification($id);
            
            $this->conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Reject replacement ticket
     */
    public function reject($id, $reason) {
        try {
            if (empty($reason)) {
                throw new Exception('Rejection reason is required');
            }
            
            $query = "UPDATE replacement_tickets 
                     SET status = 'rejected',
                         approved_by = :approved_by,
                         approved_at = CURRENT_TIMESTAMP,
                         approval_notes = :reason,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND status = 'pending_approval'";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id' => $id,
                ':approved_by' => $_SESSION['user_id'],
                ':reason' => $reason
            ]);
            
            if (!$result || $stmt->rowCount() === 0) {
                throw new Exception('Ticket not found or already processed');
            }
            
            // Log activity
            logActivity('update', 'replacement_tickets', $id, ['status' => 'rejected', 'reason' => $reason], null);
            
            // Notify requester
            $this->sendRejectionNotification($id, $reason);
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Update replacement status (Planning team action)
     */
    public function updateReplacementStatus($id, $status, $notes = null) {
        try {
            $allowed_statuses = ['replacement_processed', 'no_stock', 'completed'];
            
            if (!in_array($status, $allowed_statuses)) {
                throw new Exception('Invalid status');
            }
            
            $this->conn->beginTransaction();
            
            $query = "UPDATE replacement_tickets 
                     SET status = :status,
                         processing_notes = :notes,
                         processed_at = CASE WHEN :status IN ('replacement_processed', 'completed') THEN CURRENT_TIMESTAMP ELSE processed_at END,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id' => $id,
                ':status' => $status,
                ':notes' => $notes
            ]);
            
            // If "No Stock" selected, auto-hold the order
            if ($status === 'no_stock') {
                $ticket = $this->getTicketDetails($id);
                $this->holdOrder($ticket['order_id'], "No stock for replacement - Ticket #{$ticket['ticket_number']}");
            }
            
            // Log activity
            logActivity('update', 'replacement_tickets', $id, ['status' => $status, 'notes' => $notes], null);
            
            $this->conn->commit();
            
            return $result;
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Hold order due to no stock
     */
    private function holdOrder($order_id, $reason) {
        try {
            $query = "UPDATE orders 
                     SET status = 'on_hold',
                         notes = CONCAT(COALESCE(notes, ''), '\n\n[AUTO-HOLD] ', :reason, ' - ', NOW()),
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':order_id' => $order_id,
                ':reason' => $reason
            ]);
            
            // Log activity
            logActivity('update', 'orders', $order_id, ['status' => 'on_hold', 'reason' => $reason], null);
            
        } catch (Exception $e) {
            throw new Exception('Error holding order: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber() {
        $prefix = 'RPL';
        $date = date('Ymd');
        
        $query = "SELECT ticket_number 
                 FROM replacement_tickets 
                 WHERE ticket_number LIKE :pattern 
                 ORDER BY ticket_number DESC 
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':pattern' => $prefix . '-' . $date . '-%']);
        
        $last = $stmt->fetch();
        
        if ($last) {
            $parts = explode('-', $last['ticket_number']);
            $seq = intval($parts[2] ?? 0) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }
    
    /**
     * Send approval notification to manager
     */
    private function sendApprovalNotification($ticket_id, $department_id) {
        // This would integrate with notification system
        // For now, just log the action
        logActivity('notification', 'replacement_tickets', $ticket_id, [
            'type' => 'approval_request',
            'department_id' => $department_id
        ], null);
    }
    
    /**
     * Send planning notification after approval
     */
    private function sendPlanningNotification($ticket_id) {
        logActivity('notification', 'replacement_tickets', $ticket_id, [
            'type' => 'approved_for_planning'
        ], null);
    }
    
    /**
     * Send rejection notification
     */
    private function sendRejectionNotification($ticket_id, $reason) {
        logActivity('notification', 'replacement_tickets', $ticket_id, [
            'type' => 'rejected',
            'reason' => $reason
        ], null);
    }
    
    /**
     * Get ticket statistics
     */
    public function getStatistics($date_from = null, $date_to = null) {
        try {
            $date_from = $date_from ?? date('Y-m-01');
            $date_to = $date_to ?? date('Y-m-t');
            
            $query = "SELECT 
                        COUNT(*) as total_tickets,
                        SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'replacement_processed' THEN 1 ELSE 0 END) as processed,
                        SUM(CASE WHEN status = 'no_stock' THEN 1 ELSE 0 END) as no_stock,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(quantity_required) as total_quantity_required
                     FROM replacement_tickets
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
}
