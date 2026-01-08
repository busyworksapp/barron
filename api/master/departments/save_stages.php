<?php
/**
 * Save Department Production Stages API
 */

session_start();

require_once '../../../config/config.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

try {
    requireLogin();
    requirePermission('master.edit');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['department_id'])) {
        throw new Exception('Department ID is required');
    }
    
    if (!isset($input['stages']) || !is_array($input['stages'])) {
        throw new Exception('Stages data is required');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verify department exists
    $query = "SELECT id FROM departments WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => (int)$input['department_id']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Department not found');
    }
    
    // Start transaction
    $database->beginTransaction();
    
    try {
        $department_id = (int)$input['department_id'];
        
        // Get existing stages
        $query = "SELECT id FROM production_stages WHERE department_id = :department_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':department_id' => $department_id]);
        $existing_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $processed_ids = [];
        
        // Process each stage
        foreach ($input['stages'] as $stage) {
            if (isset($stage['id']) && $stage['id']) {
                // Update existing stage
                $stage_id = (int)$stage['id'];
                $processed_ids[] = $stage_id;
                
                $query = "UPDATE production_stages SET
                            stage_code = :stage_code,
                            stage_name = :stage_name,
                            stage_order = :stage_order,
                            estimated_duration_hours = :estimated_duration_hours,
                            is_active = :is_active
                          WHERE id = :id AND department_id = :department_id";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':stage_code' => sanitize($stage['stage_code']),
                    ':stage_name' => sanitize($stage['stage_name']),
                    ':stage_order' => (int)$stage['stage_order'],
                    ':estimated_duration_hours' => $stage['estimated_duration_hours'] ?: null,
                    ':is_active' => $stage['is_active'] ?? 1,
                    ':id' => $stage_id,
                    ':department_id' => $department_id
                ]);
                
            } else {
                // Insert new stage
                $query = "INSERT INTO production_stages (
                            department_id,
                            stage_code,
                            stage_name,
                            stage_order,
                            estimated_duration_hours,
                            is_active
                          ) VALUES (
                            :department_id,
                            :stage_code,
                            :stage_name,
                            :stage_order,
                            :estimated_duration_hours,
                            :is_active
                          )";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':department_id' => $department_id,
                    ':stage_code' => sanitize($stage['stage_code']),
                    ':stage_name' => sanitize($stage['stage_name']),
                    ':stage_order' => (int)$stage['stage_order'],
                    ':estimated_duration_hours' => $stage['estimated_duration_hours'] ?: null,
                    ':is_active' => $stage['is_active'] ?? 1
                ]);
                
                $processed_ids[] = $conn->lastInsertId();
            }
        }
        
        // Delete stages that were removed
        $to_delete = array_diff($existing_ids, $processed_ids);
        if (!empty($to_delete)) {
            $placeholders = implode(',', array_fill(0, count($to_delete), '?'));
            $query = "DELETE FROM production_stages WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->execute($to_delete);
        }
        
        // Commit transaction
        $database->commit();
        
        // Log activity
        logActivity('update', 'production_stages', $department_id, null, [
            'department_id' => $department_id,
            'stages_count' => count($input['stages'])
        ]);
        
        successResponse('Production stages saved successfully');
        
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    errorResponse($e->getMessage());
}
