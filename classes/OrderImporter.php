<?php
/**
 * Barron Production Management System
 * Order Import from Excel/D365
 */

class OrderImporter {
    private $conn;
    private $database;
    private $errors = [];
    private $imported_count = 0;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * Import orders from Excel file
     */
    public function importFromExcel($file_path, $column_mapping) {
        try {
            $this->errors = [];
            $this->imported_count = 0;
            
            // Validate file exists
            if (!file_exists($file_path)) {
                throw new Exception('File not found');
            }
            
            // Read Excel file (using simple CSV for now, can extend to XLSX)
            $rows = $this->readExcelFile($file_path);
            
            if (empty($rows)) {
                throw new Exception('No data found in file');
            }
            
            // Get headers from first row
            $headers = array_shift($rows);
            
            // Map columns
            $mapped_columns = $this->mapColumns($headers, $column_mapping);
            
            // Process each row
            $this->conn->beginTransaction();
            
            foreach ($rows as $index => $row) {
                $row_number = $index + 2; // +2 because of header and 0-index
                
                try {
                    $order_data = $this->extractOrderData($row, $mapped_columns);
                    $this->importOrder($order_data);
                    $this->imported_count++;
                } catch (Exception $e) {
                    $this->errors[] = "Row {$row_number}: " . $e->getMessage();
                }
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'imported' => $this->imported_count,
                'errors' => $this->errors,
                'total_rows' => count($rows)
            ];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Read Excel/CSV file
     */
    private function readExcelFile($file_path) {
        $rows = [];
        
        // Detect file type
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($ext === 'csv') {
            // Read CSV
            if (($handle = fopen($file_path, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
        } else {
            // For XLSX support, would integrate PhpSpreadsheet library
            throw new Exception('XLSX files not yet supported. Please use CSV format.');
        }
        
        return $rows;
    }
    
    /**
     * Map columns from file to expected fields
     */
    private function mapColumns($headers, $mapping) {
        $mapped = [];
        
        foreach ($mapping as $field => $column_index) {
            if (isset($headers[$column_index])) {
                $mapped[$field] = $column_index;
            }
        }
        
        // Validate required fields are mapped
        $required = ['order_number', 'customer_name', 'order_date', 'due_date'];
        foreach ($required as $field) {
            if (!isset($mapped[$field])) {
                throw new Exception("Required field '{$field}' not mapped");
            }
        }
        
        return $mapped;
    }
    
    /**
     * Extract order data from row
     */
    private function extractOrderData($row, $mapped_columns) {
        $data = [];
        
        foreach ($mapped_columns as $field => $index) {
            $value = isset($row[$index]) ? trim($row[$index]) : null;
            
            // Convert empty strings to null
            if ($value === '' || $value === 'NULL') {
                $value = null;
            }
            
            // Special handling for dates
            if (in_array($field, ['order_date', 'due_date']) && $value) {
                $value = $this->parseDate($value);
            }
            
            // Special handling for priority
            if ($field === 'priority' && $value) {
                $value = $this->normalizePriority($value);
            }
            
            $data[$field] = $value;
        }
        
        // Validate required fields
        if (empty($data['order_number'])) {
            throw new Exception('Order number is required');
        }
        
        if (empty($data['customer_name'])) {
            throw new Exception('Customer name is required');
        }
        
        return $data;
    }
    
    /**
     * Parse date from various formats
     */
    private function parseDate($date_string) {
        // Try common date formats
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'Y/m/d'
        ];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Try strtotime as fallback
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        throw new Exception("Invalid date format: {$date_string}");
    }
    
    /**
     * Normalize priority value
     */
    private function normalizePriority($priority) {
        $priority = strtolower(trim($priority));
        
        $mapping = [
            'urgent' => 'urgent',
            'high' => 'high',
            'normal' => 'normal',
            'low' => 'low',
            '4' => 'urgent',
            '3' => 'high',
            '2' => 'normal',
            '1' => 'low'
        ];
        
        return $mapping[$priority] ?? 'normal';
    }
    
    /**
     * Import single order
     */
    private function importOrder($data) {
        // Check if order already exists
        $check_query = "SELECT id FROM orders WHERE order_number = :order_number";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->execute([':order_number' => $data['order_number']]);
        
        if ($check_stmt->fetch()) {
            throw new Exception("Order {$data['order_number']} already exists");
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
            ':order_date' => $data['order_date'] ?? date('Y-m-d'),
            ':due_date' => $data['due_date'],
            ':priority' => $data['priority'] ?? 'normal',
            ':status' => 'pending',
            ':notes' => $data['notes'] ?? 'Imported from Excel',
            ':created_by' => $_SESSION['user_id'] ?? null
        ]);
        
        if (!$result) {
            throw new Exception('Failed to insert order');
        }
        
        $order_id = $this->conn->lastInsertId();
        
        // Log activity
        logActivity('insert', 'orders', $order_id, null, array_merge($data, ['source' => 'excel_import']));
        
        return $order_id;
    }
    
    /**
     * Import from D365 API (placeholder for future implementation)
     */
    public function importFromD365($api_config) {
        try {
            // This would integrate with Microsoft Dynamics 365 API
            // For now, return a placeholder
            
            throw new Exception('D365 integration not yet implemented. Please use Excel import.');
            
            // Future implementation would:
            // 1. Authenticate with D365 API
            // 2. Fetch sales orders
            // 3. Map D365 fields to our schema
            // 4. Import orders using same logic as Excel
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get import history/logs
     */
    public function getImportHistory($limit = 20) {
        try {
            $query = "SELECT 
                        COUNT(*) as order_count,
                        created_by,
                        DATE(created_at) as import_date,
                        u.first_name,
                        u.last_name
                      FROM orders o
                      LEFT JOIN users u ON o.created_by = u.id
                      WHERE o.notes LIKE '%Imported from%'
                      GROUP BY DATE(o.created_at), o.created_by
                      ORDER BY import_date DESC
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            throw new Exception('Error fetching import history: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate import file before processing
     */
    public function validateImportFile($file_path, $column_mapping) {
        try {
            $rows = $this->readExcelFile($file_path);
            
            if (empty($rows)) {
                return [
                    'valid' => false,
                    'message' => 'File is empty'
                ];
            }
            
            $headers = $rows[0];
            $data_rows = array_slice($rows, 1);
            
            // Validate column mapping
            try {
                $mapped = $this->mapColumns($headers, $column_mapping);
            } catch (Exception $e) {
                return [
                    'valid' => false,
                    'message' => $e->getMessage()
                ];
            }
            
            // Sample first few rows for validation
            $sample_size = min(5, count($data_rows));
            $sample_errors = [];
            
            for ($i = 0; $i < $sample_size; $i++) {
                try {
                    $this->extractOrderData($data_rows[$i], $mapped);
                } catch (Exception $e) {
                    $sample_errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
                }
            }
            
            return [
                'valid' => empty($sample_errors),
                'total_rows' => count($data_rows),
                'sample_size' => $sample_size,
                'sample_errors' => $sample_errors,
                'message' => empty($sample_errors) 
                    ? 'File validation passed' 
                    : 'File has validation errors'
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
