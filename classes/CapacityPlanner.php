<?php
/**
 * Barron Production Management System
 * Capacity Planning & Analysis
 */

class CapacityPlanner {
    private $conn;
    private $database;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * Get department capacity overview
     */
    public function getDepartmentCapacity($department_id, $date_from, $date_to) {
        try {
            // Get department info with capacity
            $dept_query = "SELECT id, department_name, capacity 
                          FROM departments 
                          WHERE id = :department_id";
            $dept_stmt = $this->conn->prepare($dept_query);
            $dept_stmt->execute([':department_id' => $department_id]);
            $department = $dept_stmt->fetch();
            
            if (!$department) {
                throw new Exception('Department not found');
            }
            
            // Get scheduled jobs in date range
            $jobs_query = "SELECT 
                            DATE(start_date) as job_date,
                            COUNT(*) as job_count,
                            SUM(quantity_planned) as total_quantity,
                            SUM(quantity_completed) as completed_quantity
                          FROM jobs
                          WHERE department_id = :department_id
                          AND start_date BETWEEN :date_from AND :date_to
                          AND status NOT IN ('cancelled')
                          GROUP BY DATE(start_date)
                          ORDER BY job_date";
            
            $jobs_stmt = $this->conn->prepare($jobs_query);
            $jobs_stmt->execute([
                ':department_id' => $department_id,
                ':date_from' => $date_from,
                ':date_to' => $date_to
            ]);
            
            $daily_capacity = [];
            while ($row = $jobs_stmt->fetch()) {
                $utilization = $department['capacity'] > 0 
                    ? ($row['job_count'] / $department['capacity']) * 100 
                    : 0;
                
                $daily_capacity[] = [
                    'date' => $row['job_date'],
                    'job_count' => $row['job_count'],
                    'total_quantity' => $row['total_quantity'],
                    'completed_quantity' => $row['completed_quantity'],
                    'capacity' => $department['capacity'],
                    'utilization_percent' => round($utilization, 2),
                    'available_capacity' => max(0, $department['capacity'] - $row['job_count']),
                    'status' => $utilization > 100 ? 'overbooked' : ($utilization > 80 ? 'high' : 'normal')
                ];
            }
            
            return [
                'department' => $department,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'daily_capacity' => $daily_capacity,
                'summary' => $this->calculateCapacitySummary($daily_capacity)
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error calculating capacity: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate capacity summary statistics
     */
    private function calculateCapacitySummary($daily_capacity) {
        if (empty($daily_capacity)) {
            return [
                'total_days' => 0,
                'avg_utilization' => 0,
                'overbooked_days' => 0,
                'high_utilization_days' => 0,
                'normal_days' => 0
            ];
        }
        
        $total = count($daily_capacity);
        $sum_utilization = 0;
        $overbooked = 0;
        $high = 0;
        $normal = 0;
        
        foreach ($daily_capacity as $day) {
            $sum_utilization += $day['utilization_percent'];
            
            if ($day['status'] === 'overbooked') $overbooked++;
            elseif ($day['status'] === 'high') $high++;
            else $normal++;
        }
        
        return [
            'total_days' => $total,
            'avg_utilization' => round($sum_utilization / $total, 2),
            'overbooked_days' => $overbooked,
            'high_utilization_days' => $high,
            'normal_days' => $normal
        ];
    }
    
    /**
     * Get all departments capacity overview
     */
    public function getAllDepartmentsCapacity($date_from, $date_to) {
        try {
            $query = "SELECT 
                        d.id,
                        d.department_name,
                        d.capacity,
                        COUNT(DISTINCT j.id) as total_jobs,
                        COUNT(DISTINCT CASE WHEN j.status = 'scheduled' THEN j.id END) as scheduled_jobs,
                        COUNT(DISTINCT CASE WHEN j.status = 'in_progress' THEN j.id END) as in_progress_jobs,
                        COUNT(DISTINCT CASE WHEN j.status = 'completed' THEN j.id END) as completed_jobs
                      FROM departments d
                      LEFT JOIN jobs j ON d.id = j.department_id 
                        AND j.start_date BETWEEN :date_from AND :date_to
                        AND j.status NOT IN ('cancelled')
                      WHERE d.status = 'active'
                      GROUP BY d.id
                      ORDER BY d.department_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':date_from' => $date_from,
                ':date_to' => $date_to
            ]);
            
            $departments = [];
            while ($dept = $stmt->fetch()) {
                $days = (strtotime($date_to) - strtotime($date_from)) / 86400 + 1;
                $total_capacity = $dept['capacity'] * $days;
                $utilization = $total_capacity > 0 
                    ? ($dept['total_jobs'] / $total_capacity) * 100 
                    : 0;
                
                $dept['total_capacity'] = $total_capacity;
                $dept['utilization_percent'] = round($utilization, 2);
                $dept['available_capacity'] = max(0, $total_capacity - $dept['total_jobs']);
                $dept['status'] = $utilization > 100 ? 'overbooked' : ($utilization > 80 ? 'high' : 'normal');
                
                $departments[] = $dept;
            }
            
            return $departments;
            
        } catch (Exception $e) {
            throw new Exception('Error fetching department capacities: ' . $e->getMessage());
        }
    }
    
    /**
     * Find available slots for job scheduling
     */
    public function findAvailableSlots($department_id, $required_capacity, $date_from, $date_to) {
        try {
            $query = "SELECT 
                        DATE(start_date) as slot_date,
                        COUNT(*) as scheduled_count
                      FROM jobs
                      WHERE department_id = :department_id
                      AND start_date BETWEEN :date_from AND :date_to
                      AND status IN ('scheduled', 'in_progress')
                      GROUP BY DATE(start_date)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':department_id' => $department_id,
                ':date_from' => $date_from,
                ':date_to' => $date_to
            ]);
            
            $scheduled = [];
            while ($row = $stmt->fetch()) {
                $scheduled[$row['slot_date']] = $row['scheduled_count'];
            }
            
            // Get department capacity
            $dept_query = "SELECT capacity FROM departments WHERE id = :department_id";
            $dept_stmt = $this->conn->prepare($dept_query);
            $dept_stmt->execute([':department_id' => $department_id]);
            $dept = $dept_stmt->fetch();
            $capacity = $dept['capacity'] ?? 0;
            
            // Find available slots
            $available_slots = [];
            $current = strtotime($date_from);
            $end = strtotime($date_to);
            
            while ($current <= $end) {
                $date = date('Y-m-d', $current);
                $scheduled_count = $scheduled[$date] ?? 0;
                $available = max(0, $capacity - $scheduled_count);
                
                if ($available >= $required_capacity) {
                    $available_slots[] = [
                        'date' => $date,
                        'available_capacity' => $available,
                        'scheduled_jobs' => $scheduled_count,
                        'utilization_percent' => round(($scheduled_count / $capacity) * 100, 2)
                    ];
                }
                
                $current = strtotime('+1 day', $current);
            }
            
            return $available_slots;
            
        } catch (Exception $e) {
            throw new Exception('Error finding available slots: ' . $e->getMessage());
        }
    }
    
    /**
     * Get smart replacement suggestions for on-hold jobs
     */
    public function getReplacementSuggestions($department_id, $date) {
        try {
            // Find unscheduled orders that could fill the slot
            $query = "SELECT 
                        o.id as order_id,
                        o.order_number,
                        o.customer_name,
                        o.priority,
                        o.due_date,
                        COUNT(oi.id) as item_count,
                        DATEDIFF(o.due_date, :date) as days_until_due
                      FROM orders o
                      INNER JOIN order_items oi ON o.id = oi.order_id
                      LEFT JOIN jobs j ON o.id = j.order_id AND j.department_id = :department_id
                      WHERE o.status IN ('pending', 'confirmed')
                      AND j.id IS NULL
                      AND o.due_date >= :date
                      ORDER BY 
                        o.priority DESC,
                        o.due_date ASC,
                        o.order_date ASC
                      LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':department_id' => $department_id,
                ':date' => $date
            ]);
            
            $suggestions = [];
            while ($row = $stmt->fetch()) {
                $row['urgency_score'] = $this->calculateUrgencyScore(
                    $row['priority'],
                    $row['days_until_due']
                );
                $suggestions[] = $row;
            }
            
            return $suggestions;
            
        } catch (Exception $e) {
            throw new Exception('Error getting replacement suggestions: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate urgency score for prioritization
     */
    private function calculateUrgencyScore($priority, $days_until_due) {
        $priority_weights = [
            'urgent' => 100,
            'high' => 75,
            'normal' => 50,
            'low' => 25
        ];
        
        $priority_score = $priority_weights[$priority] ?? 50;
        
        // Time urgency (inverse of days remaining)
        $time_score = 0;
        if ($days_until_due <= 3) $time_score = 50;
        elseif ($days_until_due <= 7) $time_score = 30;
        elseif ($days_until_due <= 14) $time_score = 15;
        else $time_score = 5;
        
        return $priority_score + $time_score;
    }
    
    /**
     * Validate if job can be scheduled
     */
    public function validateScheduling($department_id, $start_date, $quantity = 1) {
        try {
            // Get department capacity
            $dept_query = "SELECT capacity FROM departments WHERE id = :department_id";
            $dept_stmt = $this->conn->prepare($dept_query);
            $dept_stmt->execute([':department_id' => $department_id]);
            $dept = $dept_stmt->fetch();
            
            if (!$dept) {
                return [
                    'can_schedule' => false,
                    'message' => 'Department not found'
                ];
            }
            
            $capacity = $dept['capacity'];
            
            // Check current utilization on that date
            $util_query = "SELECT COUNT(*) as scheduled_count
                          FROM jobs
                          WHERE department_id = :department_id
                          AND DATE(start_date) = :date
                          AND status IN ('scheduled', 'in_progress')";
            
            $util_stmt = $this->conn->prepare($util_query);
            $util_stmt->execute([
                ':department_id' => $department_id,
                ':date' => $start_date
            ]);
            
            $util = $util_stmt->fetch();
            $scheduled = $util['scheduled_count'];
            $available = max(0, $capacity - $scheduled);
            
            $can_schedule = $available >= $quantity;
            $utilization = $capacity > 0 ? (($scheduled + $quantity) / $capacity) * 100 : 0;
            
            return [
                'can_schedule' => $can_schedule,
                'capacity' => $capacity,
                'scheduled' => $scheduled,
                'available' => $available,
                'requested' => $quantity,
                'utilization_percent' => round($utilization, 2),
                'message' => $can_schedule 
                    ? 'Capacity available' 
                    : "Insufficient capacity (need {$quantity}, available {$available})",
                'warning' => $utilization > 80 && $can_schedule 
                    ? 'High utilization - consider alternative dates' 
                    : null
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error validating schedule: ' . $e->getMessage());
        }
    }
    
    /**
     * Get capacity trends over time
     */
    public function getCapacityTrends($department_id, $weeks = 4) {
        try {
            $date_from = date('Y-m-d', strtotime("-{$weeks} weeks"));
            $date_to = date('Y-m-d');
            
            $query = "SELECT 
                        YEARWEEK(start_date) as year_week,
                        WEEK(start_date) as week_num,
                        YEAR(start_date) as year,
                        COUNT(*) as job_count,
                        SUM(quantity_planned) as planned_quantity,
                        SUM(quantity_completed) as completed_quantity
                      FROM jobs
                      WHERE department_id = :department_id
                      AND start_date BETWEEN :date_from AND :date_to
                      AND status NOT IN ('cancelled')
                      GROUP BY YEARWEEK(start_date)
                      ORDER BY year_week";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':department_id' => $department_id,
                ':date_from' => $date_from,
                ':date_to' => $date_to
            ]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching capacity trends: ' . $e->getMessage());
        }
    }
}
