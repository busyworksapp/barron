<?php
/**
 * Barron Production Management System
 * Planning Module - Core Business Logic
 */

class Planning {
    private $conn;
    private $database;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * Get all orders with optional filters
     */
    public function getOrders($filters = []) {
        try {
            $where = ["1=1"];
            $params = [];
            
            if (isset($filters['status'])) {
                $where[] = "o.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (isset($filters['priority'])) {
                $where[] = "o.priority = :priority";
                $params[':priority'] = $filters['priority'];
            }
            
            if (isset($filters['customer'])) {
                $where[] = "o.customer_name LIKE :customer";
                $params[':customer'] = '%' . $filters['customer'] . '%';
            }
            
            if (isset($filters['from_date'])) {
                $where[] = "o.order_date >= :from_date";
                $params[':from_date'] = $filters['from_date'];
            }
            
            if (isset($filters['to_date'])) {
                $where[] = "o.order_date <= :to_date";
                $params[':to_date'] = $filters['to_date'];
            }
            
            $query = "SELECT o.*, 
                             COUNT(DISTINCT oi.id) as item_count,
                             COUNT(DISTINCT j.id) as job_count,
                             SUM(CASE WHEN j.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                             u.first_name, u.last_name
                      FROM orders o
                      LEFT JOIN order_items oi ON o.id = oi.order_id
                      LEFT JOIN jobs j ON o.id = j.order_id
                      LEFT JOIN users u ON o.created_by = u.id
                      WHERE " . implode(' AND ', $where) . "
                      GROUP BY o.id
                      ORDER BY o.due_date ASC, o.priority DESC, o.order_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching orders: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single order with items and jobs
     */
    public function getOrderDetails($order_id) {
        try {
            // Get order
            $query = "SELECT o.*, 
                             u.first_name, u.last_name
                      FROM orders o
                      LEFT JOIN users u ON o.created_by = u.id
                      WHERE o.id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':order_id' => $order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Get order items
            $items_query = "SELECT oi.*, 
                                   p.product_name, p.product_code
                            FROM order_items oi
                            LEFT JOIN products p ON oi.product_id = p.id
                            WHERE oi.order_id = :order_id";
            
            $items_stmt = $this->conn->prepare($items_query);
            $items_stmt->execute([':order_id' => $order_id]);
            $order['items'] = $items_stmt->fetchAll();
            
            // Get jobs
            $jobs_query = "SELECT j.*, 
                                  d.department_name,
                                  p.product_name,
                                  m.machine_name,
                                  u.first_name as operator_first,
                                  u.last_name as operator_last
                           FROM jobs j
                           LEFT JOIN departments d ON j.department_id = d.id
                           LEFT JOIN products p ON j.product_id = p.id
                           LEFT JOIN machines m ON j.machine_id = m.id
                           LEFT JOIN users u ON j.assigned_operator_id = u.id
                           WHERE j.order_id = :order_id
                           ORDER BY j.created_at DESC";
            
            $jobs_stmt = $this->conn->prepare($jobs_query);
            $jobs_stmt->execute([':order_id' => $order_id]);
            $order['jobs'] = $jobs_stmt->fetchAll();
            
            return $order;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Create new order
     */
    public function createOrder($data) {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            $required = ['order_number', 'customer_name', 'order_date', 'due_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Check if order number exists
            $check_query = "SELECT id FROM orders WHERE order_number = :order_number";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([':order_number' => $data['order_number']]);
            
            if ($check_stmt->fetch()) {
                throw new Exception('Order number already exists');
            }
            
            // Insert order
            $query = "INSERT INTO orders 
                      (order_number, customer_name, customer_email, customer_phone,
                       order_date, due_date, priority, status, notes, created_by)
                      VALUES 
                      (:order_number, :customer_name, :customer_email, :customer_phone,
                       :order_date, :due_date, :priority, :status, :notes, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':order_number' => $data['order_number'],
                ':customer_name' => $data['customer_name'],
                ':customer_email' => $data['customer_email'] ?? null,
                ':customer_phone' => $data['customer_phone'] ?? null,
                ':order_date' => $data['order_date'],
                ':due_date' => $data['due_date'],
                ':priority' => $data['priority'] ?? 'normal',
                ':status' => $data['status'] ?? 'pending',
                ':notes' => $data['notes'] ?? null,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            $order_id = $this->conn->lastInsertId();
            
            // Insert order items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $item_query = "INSERT INTO order_items 
                               (order_id, product_id, quantity, unit_price, notes)
                               VALUES (:order_id, :product_id, :quantity, :unit_price, :notes)";
                $item_stmt = $this->conn->prepare($item_query);
                
                foreach ($data['items'] as $item) {
                    $item_stmt->execute([
                        ':order_id' => $order_id,
                        ':product_id' => $item['product_id'],
                        ':quantity' => $item['quantity'],
                        ':unit_price' => $item['unit_price'] ?? 0,
                        ':notes' => $item['notes'] ?? null
                    ]);
                }
            }
            
            $this->conn->commit();
            
            logActivity('insert', 'orders', $order_id, null, $data);
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'id' => $order_id
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * Update order
     */
    public function updateOrder($order_id, $data) {
        try {
            // Get old data
            $old_data = $this->getOrderDetails($order_id);
            
            $fields = [];
            $params = [':id' => $order_id];
            
            $allowed_fields = ['customer_name', 'customer_email', 'customer_phone',
                              'order_date', 'due_date', 'priority', 'status', 'notes'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception('No fields to update');
            }
            
            $query = "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity('update', 'orders', $order_id, $old_data, $data);
                return [
                    'success' => true,
                    'message' => 'Order updated successfully'
                ];
            }
            
            throw new Exception('Failed to update order');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get jobs with filters
     */
    public function getJobs($filters = []) {
        try {
            $where = ["1=1"];
            $params = [];
            
            if (isset($filters['status'])) {
                $where[] = "j.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (isset($filters['department_id'])) {
                $where[] = "j.department_id = :department_id";
                $params[':department_id'] = $filters['department_id'];
            }
            
            if (isset($filters['operator_id'])) {
                $where[] = "j.assigned_operator_id = :operator_id";
                $params[':operator_id'] = $filters['operator_id'];
            }
            
            if (isset($filters['from_date'])) {
                $where[] = "j.start_date >= :from_date";
                $params[':from_date'] = $filters['from_date'];
            }
            
            if (isset($filters['to_date'])) {
                $where[] = "j.start_date <= :to_date";
                $params[':to_date'] = $filters['to_date'];
            }
            
            $query = "SELECT j.*, 
                             o.order_number, o.customer_name,
                             p.product_name, p.product_code,
                             d.department_name,
                             m.machine_name,
                             u.first_name, u.last_name
                      FROM jobs j
                      LEFT JOIN orders o ON j.order_id = o.id
                      LEFT JOIN products p ON j.product_id = p.id
                      LEFT JOIN departments d ON j.department_id = d.id
                      LEFT JOIN machines m ON j.machine_id = m.id
                      LEFT JOIN users u ON j.assigned_operator_id = u.id
                      WHERE " . implode(' AND ', $where) . "
                      ORDER BY j.start_date ASC, j.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching jobs: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new job
     */
    public function createJob($data) {
        try {
            // Validate required fields
            $required = ['job_number', 'product_id', 'quantity_planned'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Check job number uniqueness
            $check_query = "SELECT id FROM jobs WHERE job_number = :job_number";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([':job_number' => $data['job_number']]);
            
            if ($check_stmt->fetch()) {
                throw new Exception('Job number already exists');
            }
            
            $query = "INSERT INTO jobs 
                      (job_number, order_id, product_id, department_id, quantity_planned,
                       machine_id, assigned_operator_id, start_date, status, notes, created_by)
                      VALUES 
                      (:job_number, :order_id, :product_id, :department_id, :quantity_planned,
                       :machine_id, :assigned_operator_id, :start_date, :status, :notes, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':job_number' => $data['job_number'],
                ':order_id' => $data['order_id'] ?? null,
                ':product_id' => $data['product_id'],
                ':department_id' => $data['department_id'] ?? null,
                ':quantity_planned' => $data['quantity_planned'],
                ':machine_id' => $data['machine_id'] ?? null,
                ':assigned_operator_id' => $data['assigned_operator_id'] ?? null,
                ':start_date' => $data['start_date'] ?? null,
                ':status' => $data['status'] ?? 'scheduled',
                ':notes' => $data['notes'] ?? null,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            $job_id = $this->conn->lastInsertId();
            
            logActivity('insert', 'jobs', $job_id, null, $data);
            
            return [
                'success' => true,
                'message' => 'Job created successfully',
                'id' => $job_id
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Update job status
     */
    public function updateJobStatus($job_id, $status, $notes = null) {
        try {
            $query = "UPDATE jobs SET status = :status, notes = COALESCE(:notes, notes) WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':status' => $status,
                ':notes' => $notes,
                ':id' => $job_id
            ]);
            
            if ($result) {
                logActivity('update', 'jobs', $job_id, null, ['status' => $status, 'notes' => $notes]);
                return ['success' => true, 'message' => 'Job status updated'];
            }
            
            throw new Exception('Failed to update job status');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Total active orders
            $query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'confirmed', 'in_production')";
            $stmt = $this->conn->query($query);
            $stats['active_orders'] = $stmt->fetch()['count'];
            
            // Jobs by status
            $query = "SELECT status, COUNT(*) as count FROM jobs GROUP BY status";
            $stmt = $this->conn->query($query);
            $stats['jobs_by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Orders by priority
            $query = "SELECT priority, COUNT(*) as count FROM orders 
                     WHERE status IN ('pending', 'confirmed', 'in_production')
                     GROUP BY priority";
            $stmt = $this->conn->query($query);
            $stats['orders_by_priority'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Overdue orders
            $query = "SELECT COUNT(*) as count FROM orders 
                     WHERE due_date < CURDATE() 
                     AND status NOT IN ('completed', 'cancelled')";
            $stmt = $this->conn->query($query);
            $stats['overdue_orders'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            throw new Exception('Error fetching dashboard stats: ' . $e->getMessage());
        }
    }
}
