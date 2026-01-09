<?php
/**
 * Barron Production Management System
 * Production Stages Management Class
 */

class ProductionStages {
    private $conn;
    private $database;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * Get all production stages for a department
     */
    public function getByDepartment($department_id) {
        try {
            $query = "SELECT ps.*, 
                             d.department_name, 
                             u.first_name, 
                             u.last_name
                      FROM production_stages ps
                      LEFT JOIN departments d ON ps.department_id = d.id
                      LEFT JOIN users u ON ps.created_by = u.id
                      WHERE ps.department_id = :department_id
                      ORDER BY ps.stage_order ASC, ps.stage_name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':department_id' => $department_id]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching production stages: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all active production stages for a department
     */
    public function getActivByDepartment($department_id) {
        try {
            $query = "SELECT * FROM production_stages
                      WHERE department_id = :department_id 
                      AND is_active = 1
                      ORDER BY stage_order ASC, stage_name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':department_id' => $department_id]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching active stages: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single production stage by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT ps.*, 
                             d.department_name
                      FROM production_stages ps
                      LEFT JOIN departments d ON ps.department_id = d.id
                      WHERE ps.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching production stage: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new production stage
     */
    public function create($data) {
        try {
            // Validate required fields
            $required = ['department_id', 'stage_name', 'stage_code'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Check if stage code already exists for this department
            $check_query = "SELECT id FROM production_stages 
                           WHERE department_id = :department_id 
                           AND stage_code = :stage_code";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([
                ':department_id' => $data['department_id'],
                ':stage_code' => $data['stage_code']
            ]);
            
            if ($check_stmt->fetch()) {
                throw new Exception('Stage code already exists for this department');
            }
            
            // Get next order number if not provided
            if (!isset($data['stage_order'])) {
                $order_query = "SELECT COALESCE(MAX(stage_order), 0) + 1 as next_order 
                               FROM production_stages 
                               WHERE department_id = :department_id";
                $order_stmt = $this->conn->prepare($order_query);
                $order_stmt->execute([':department_id' => $data['department_id']]);
                $order_result = $order_stmt->fetch();
                $data['stage_order'] = $order_result['next_order'];
            }
            
            $query = "INSERT INTO production_stages 
                      (department_id, stage_name, stage_code, stage_order, 
                       description, estimated_hours, is_active, created_by)
                      VALUES 
                      (:department_id, :stage_name, :stage_code, :stage_order,
                       :description, :estimated_hours, :is_active, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':department_id' => $data['department_id'],
                ':stage_name' => $data['stage_name'],
                ':stage_code' => $data['stage_code'],
                ':stage_order' => $data['stage_order'],
                ':description' => $data['description'] ?? null,
                ':estimated_hours' => $data['estimated_hours'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            if ($result) {
                $id = $this->conn->lastInsertId();
                logActivity('insert', 'production_stages', $id, null, $data);
                return [
                    'success' => true,
                    'message' => 'Production stage created successfully',
                    'id' => $id
                ];
            }
            
            throw new Exception('Failed to create production stage');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Update a production stage
     */
    public function update($id, $data) {
        try {
            // Get old data for logging
            $old_data = $this->getById($id);
            if (!$old_data) {
                throw new Exception('Production stage not found');
            }
            
            // Check if stage code is being changed and if it's unique
            if (isset($data['stage_code']) && $data['stage_code'] !== $old_data['stage_code']) {
                $check_query = "SELECT id FROM production_stages 
                               WHERE department_id = :department_id 
                               AND stage_code = :stage_code 
                               AND id != :id";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->execute([
                    ':department_id' => $old_data['department_id'],
                    ':stage_code' => $data['stage_code'],
                    ':id' => $id
                ]);
                
                if ($check_stmt->fetch()) {
                    throw new Exception('Stage code already exists for this department');
                }
            }
            
            $fields = [];
            $params = [':id' => $id];
            
            $allowed_fields = ['stage_name', 'stage_code', 'stage_order', 'description', 'estimated_hours', 'is_active'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception('No fields to update');
            }
            
            $query = "UPDATE production_stages SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity('update', 'production_stages', $id, $old_data, $data);
                return [
                    'success' => true,
                    'message' => 'Production stage updated successfully'
                ];
            }
            
            throw new Exception('Failed to update production stage');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Delete a production stage
     */
    public function delete($id) {
        try {
            // Check if stage is being used in any jobs
            $check_query = "SELECT COUNT(*) as count FROM jobs WHERE production_stage_id = :id";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([':id' => $id]);
            $usage = $check_stmt->fetch();
            
            if ($usage['count'] > 0) {
                throw new Exception('Cannot delete stage: it is being used in ' . $usage['count'] . ' job(s)');
            }
            
            // Get data for logging
            $old_data = $this->getById($id);
            
            $query = "DELETE FROM production_stages WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([':id' => $id]);
            
            if ($result) {
                logActivity('delete', 'production_stages', $id, $old_data, null);
                return [
                    'success' => true,
                    'message' => 'Production stage deleted successfully'
                ];
            }
            
            throw new Exception('Failed to delete production stage');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Reorder production stages
     */
    public function reorder($department_id, $order_array) {
        try {
            $this->conn->beginTransaction();
            
            $query = "UPDATE production_stages SET stage_order = :order WHERE id = :id AND department_id = :department_id";
            $stmt = $this->conn->prepare($query);
            
            foreach ($order_array as $order => $stage_id) {
                $stmt->execute([
                    ':order' => $order + 1,
                    ':id' => $stage_id,
                    ':department_id' => $department_id
                ]);
            }
            
            $this->conn->commit();
            
            logActivity('update', 'production_stages', null, null, [
                'action' => 'reorder',
                'department_id' => $department_id,
                'new_order' => $order_array
            ]);
            
            return [
                'success' => true,
                'message' => 'Production stages reordered successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * Toggle active status
     */
    public function toggleActive($id) {
        try {
            $stage = $this->getById($id);
            if (!$stage) {
                throw new Exception('Production stage not found');
            }
            
            $new_status = $stage['is_active'] ? 0 : 1;
            
            return $this->update($id, ['is_active' => $new_status]);
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get all departments with their production stages
     */
    public function getAllWithDepartments() {
        try {
            $query = "SELECT d.id as department_id, 
                             d.department_name, 
                             d.code as department_code,
                             COUNT(ps.id) as stage_count,
                             SUM(CASE WHEN ps.is_active = 1 THEN 1 ELSE 0 END) as active_count
                      FROM departments d
                      LEFT JOIN production_stages ps ON d.id = ps.department_id
                      WHERE d.status = 'active'
                      GROUP BY d.id
                      ORDER BY d.department_name";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching departments with stages: ' . $e->getMessage());
        }
    }
}
