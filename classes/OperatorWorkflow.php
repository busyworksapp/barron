<?php
/**
 * Barron Production Management System
 * Operator Workflow Class
 * 
 * Handles operator actions on the production floor:
 * - Job scanning and assignment
 * - Stage progression
 * - Quick defect reporting
 * - Operator performance tracking
 * - Real-time status updates
 */

class OperatorWorkflow {
    private $db;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }
    
    /**
     * Scan job - Retrieve job details by job number or QR code
     */
    public function scanJob($job_identifier) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    j.id,
                    j.job_number,
                    j.quantity,
                    j.completed_quantity,
                    j.current_stage_id,
                    j.status,
                    j.priority,
                    j.due_date,
                    j.created_at,
                    o.order_number,
                    o.customer,
                    p.product_code,
                    p.product_name,
                    p.description as product_description,
                    ps.stage_name as current_stage_name,
                    ps.department_id as current_department_id,
                    d.department_name as current_department_name,
                    u.username as assigned_operator
                FROM jobs j
                LEFT JOIN orders o ON j.order_id = o.id
                LEFT JOIN products p ON j.product_id = p.id
                LEFT JOIN production_stages ps ON j.current_stage_id = ps.id
                LEFT JOIN departments d ON ps.department_id = d.id
                LEFT JOIN users u ON j.assigned_operator_id = u.id
                WHERE j.job_number = ? AND j.status != 'cancelled'
            ");
            
            $stmt->execute([$job_identifier]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                return null;
            }
            
            // Get job stages (workflow)
            $job['stages'] = $this->getJobStages($job['id']);
            
            // Get current stage progress
            $job['stage_progress'] = $this->getStageProgress($job['id'], $job['current_stage_id']);
            
            // Get recent activity
            $job['recent_activity'] = $this->getRecentActivity($job['id'], 5);
            
            // Calculate completion percentage
            $job['completion_percentage'] = $job['quantity'] > 0 
                ? round(($job['completed_quantity'] / $job['quantity']) * 100, 1) 
                : 0;
            
            return $job;
            
        } catch (PDOException $e) {
            error_log("Error scanning job: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get job stages workflow
     */
    private function getJobStages($job_id) {
        $stmt = $this->db->prepare("
            SELECT 
                js.id,
                js.stage_id,
                js.sequence_order,
                js.status,
                js.started_at,
                js.completed_at,
                ps.stage_name,
                ps.department_id,
                d.department_name
            FROM job_stages js
            JOIN production_stages ps ON js.stage_id = ps.id
            JOIN departments d ON ps.department_id = d.id
            WHERE js.job_id = ?
            ORDER BY js.sequence_order ASC
        ");
        
        $stmt->execute([$job_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get stage progress details
     */
    private function getStageProgress($job_id, $stage_id) {
        $stmt = $this->db->prepare("
            SELECT 
                status,
                started_at,
                completed_at,
                TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW())) as duration_minutes
            FROM job_stages
            WHERE job_id = ? AND stage_id = ?
        ");
        
        $stmt->execute([$job_id, $stage_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent activity for job
     */
    private function getRecentActivity($job_id, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                activity_type,
                details,
                created_at,
                u.username
            FROM activity_log a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.related_type = 'job' AND a.related_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$job_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Start working on a job (operator assignment)
     */
    public function startJob($job_id, $operator_id, $stage_id = null) {
        try {
            $this->db->beginTransaction();
            
            // Get current job details
            $stmt = $this->db->prepare("SELECT current_stage_id, status FROM jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                throw new Exception("Job not found");
            }
            
            $stage_id = $stage_id ?? $job['current_stage_id'];
            
            // Assign operator to job
            $stmt = $this->db->prepare("
                UPDATE jobs 
                SET assigned_operator_id = ?,
                    status = 'in_progress',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$operator_id, $job_id]);
            
            // Update job stage status
            $stmt = $this->db->prepare("
                UPDATE job_stages 
                SET status = 'in_progress',
                    started_at = COALESCE(started_at, NOW())
                WHERE job_id = ? AND stage_id = ?
            ");
            $stmt->execute([$job_id, $stage_id]);
            
            // Log activity
            $this->logActivity('job_started', $job_id, $operator_id, "Operator started working on job at stage");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error starting job: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update job progress (quantity completed)
     */
    public function updateProgress($job_id, $completed_quantity, $operator_id, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Get job details
            $stmt = $this->db->prepare("SELECT quantity, completed_quantity, current_stage_id FROM jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                throw new Exception("Job not found");
            }
            
            // Validate quantity
            if ($completed_quantity > $job['quantity']) {
                throw new Exception("Completed quantity cannot exceed total quantity");
            }
            
            // Update job
            $stmt = $this->db->prepare("
                UPDATE jobs 
                SET completed_quantity = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$completed_quantity, $job_id]);
            
            // Log activity
            $details = "Updated progress: {$completed_quantity}/{$job['quantity']} units";
            if ($notes) {
                $details .= " - Notes: {$notes}";
            }
            $this->logActivity('progress_updated', $job_id, $operator_id, $details);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating progress: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Complete current stage and move to next
     */
    public function completeStage($job_id, $operator_id, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Get current stage
            $stmt = $this->db->prepare("
                SELECT j.current_stage_id, js.sequence_order
                FROM jobs j
                JOIN job_stages js ON j.id = js.job_id AND j.current_stage_id = js.stage_id
                WHERE j.id = ?
            ");
            $stmt->execute([$job_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                throw new Exception("Current stage not found");
            }
            
            // Mark current stage as completed
            $stmt = $this->db->prepare("
                UPDATE job_stages 
                SET status = 'completed',
                    completed_at = NOW()
                WHERE job_id = ? AND stage_id = ?
            ");
            $stmt->execute([$job_id, $current['current_stage_id']]);
            
            // Get next stage
            $stmt = $this->db->prepare("
                SELECT stage_id
                FROM job_stages
                WHERE job_id = ? AND sequence_order > ?
                ORDER BY sequence_order ASC
                LIMIT 1
            ");
            $stmt->execute([$job_id, $current['sequence_order']]);
            $next_stage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($next_stage) {
                // Move to next stage
                $stmt = $this->db->prepare("
                    UPDATE jobs 
                    SET current_stage_id = ?,
                        status = 'pending',
                        assigned_operator_id = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$next_stage['stage_id'], $job_id]);
                
                // Mark next stage as pending
                $stmt = $this->db->prepare("
                    UPDATE job_stages 
                    SET status = 'pending'
                    WHERE job_id = ? AND stage_id = ?
                ");
                $stmt->execute([$job_id, $next_stage['stage_id']]);
                
                $this->logActivity('stage_completed', $job_id, $operator_id, "Stage completed, moved to next stage");
            } else {
                // No more stages - job is complete
                $stmt = $this->db->prepare("
                    UPDATE jobs 
                    SET status = 'completed',
                        assigned_operator_id = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$job_id]);
                
                $this->logActivity('job_completed', $job_id, $operator_id, "Job completed - all stages finished");
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error completing stage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Report defect quickly from operator interface
     */
    public function reportDefect($data) {
        try {
            $this->db->beginTransaction();
            
            // Get job details
            $stmt = $this->db->prepare("
                SELECT j.product_id, j.current_stage_id, ps.department_id
                FROM jobs j
                JOIN production_stages ps ON j.current_stage_id = ps.id
                WHERE j.id = ?
            ");
            $stmt->execute([$data['job_id']]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                throw new Exception("Job not found");
            }
            
            // Generate defect number
            $defect_number = $this->generateDefectNumber();
            
            // Insert defect
            $stmt = $this->db->prepare("
                INSERT INTO defects (
                    defect_number, job_id, product_id, department_id,
                    quantity, severity, description, requires_replacement,
                    reported_by, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            
            $stmt->execute([
                $defect_number,
                $data['job_id'],
                $job['product_id'],
                $job['department_id'],
                $data['quantity'],
                $data['severity'] ?? 'medium',
                $data['description'],
                $data['requires_replacement'] ?? 0,
                $data['operator_id'],
            ]);
            
            $defect_id = $this->db->lastInsertId();
            
            // If replacement required, create ticket
            if (!empty($data['requires_replacement'])) {
                require_once __DIR__ . '/ReplacementTicket.php';
                $ticketClass = new ReplacementTicket();
                $ticketClass->createFromDefect($defect_id, [
                    'urgency' => $data['severity'] === 'critical' ? 'urgent' : 'normal'
                ]);
            }
            
            // Log activity
            $this->logActivity('defect_reported', $data['job_id'], $data['operator_id'], 
                "Defect reported: {$defect_number} - {$data['quantity']} units");
            
            $this->db->commit();
            return $defect_id;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error reporting defect: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique defect number
     */
    private function generateDefectNumber() {
        $date = date('Ymd');
        $prefix = "DEF-{$date}-";
        
        $stmt = $this->db->prepare("
            SELECT defect_number 
            FROM defects 
            WHERE defect_number LIKE ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute(["{$prefix}%"]);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $last_number = (int)substr($last['defect_number'], -4);
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get operator's active jobs
     */
    public function getOperatorJobs($operator_id, $status = 'in_progress') {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    j.id,
                    j.job_number,
                    j.quantity,
                    j.completed_quantity,
                    j.status,
                    j.priority,
                    j.due_date,
                    o.order_number,
                    p.product_code,
                    p.product_name,
                    ps.stage_name as current_stage_name,
                    d.department_name
                FROM jobs j
                LEFT JOIN orders o ON j.order_id = o.id
                LEFT JOIN products p ON j.product_id = p.id
                LEFT JOIN production_stages ps ON j.current_stage_id = ps.id
                LEFT JOIN departments d ON ps.department_id = d.id
                WHERE j.assigned_operator_id = ? AND j.status = ?
                ORDER BY j.priority DESC, j.due_date ASC
            ");
            
            $stmt->execute([$operator_id, $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting operator jobs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get available jobs for department (not assigned)
     */
    public function getAvailableJobs($department_id, $limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    j.id,
                    j.job_number,
                    j.quantity,
                    j.completed_quantity,
                    j.status,
                    j.priority,
                    j.due_date,
                    j.created_at,
                    o.order_number,
                    p.product_code,
                    p.product_name,
                    ps.stage_name as current_stage_name
                FROM jobs j
                LEFT JOIN orders o ON j.order_id = o.id
                LEFT JOIN products p ON j.product_id = p.id
                LEFT JOIN production_stages ps ON j.current_stage_id = ps.id
                WHERE ps.department_id = ? 
                    AND j.assigned_operator_id IS NULL
                    AND j.status IN ('pending', 'in_progress')
                ORDER BY j.priority DESC, j.due_date ASC
                LIMIT ?
            ");
            
            $stmt->execute([$department_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting available jobs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get operator performance statistics
     */
    public function getOperatorStats($operator_id, $date_from = null, $date_to = null) {
        try {
            $date_from = $date_from ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $date_to ?? date('Y-m-d');
            
            // Jobs completed
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as jobs_completed
                FROM activity_log
                WHERE user_id = ? 
                    AND activity_type = 'job_completed'
                    AND DATE(created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$operator_id, $date_from, $date_to]);
            $completed = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Stages completed
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as stages_completed
                FROM activity_log
                WHERE user_id = ? 
                    AND activity_type = 'stage_completed'
                    AND DATE(created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$operator_id, $date_from, $date_to]);
            $stages = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Defects reported
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as defects_reported
                FROM defects
                WHERE reported_by = ?
                    AND DATE(created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$operator_id, $date_from, $date_to]);
            $defects = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Active jobs
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as active_jobs
                FROM jobs
                WHERE assigned_operator_id = ? AND status = 'in_progress'
            ");
            $stmt->execute([$operator_id]);
            $active = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'jobs_completed' => $completed['jobs_completed'],
                'stages_completed' => $stages['stages_completed'],
                'defects_reported' => $defects['defects_reported'],
                'active_jobs' => $active['active_jobs'],
                'period' => [
                    'from' => $date_from,
                    'to' => $date_to
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting operator stats: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log activity
     */
    private function logActivity($activity_type, $job_id, $user_id, $details) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (user_id, activity_type, related_type, related_id, details, created_at)
                VALUES (?, ?, 'job', ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $activity_type, $job_id, $details]);
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
