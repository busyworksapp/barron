<?php
/**
 * BOM (Bill of Materials) & Finance Manager
 * Handles BOM creation, cost roll-up, material requirements, and accounting integration
 */
class BOMManager
{
    protected $db;
    
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
    
    // BOM Management
    
    public function createBOM(int $productId, string $version = '1.0'): int
    {
        $stmt = $this->db->prepare('INSERT INTO boms (product_id, version, status, created_at) VALUES (:product_id, :version, :status, NOW())');
        $stmt->execute([
            ':product_id' => $productId,
            ':version' => $version,
            ':status' => 'draft'
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function getBOMs(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT b.*, p.name as product_name, p.sku FROM boms b JOIN products p ON b.product_id = p.id WHERE b.product_id = :product_id ORDER BY b.version DESC');
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBOMDetails(int $bomId): ?array
    {
        $stmt = $this->db->prepare('SELECT b.*, p.name as product_name, p.sku FROM boms b JOIN products p ON b.product_id = p.id WHERE b.id = :id');
        $stmt->execute([':id' => $bomId]);
        $bom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bom) return null;
        
        // Get BOM items
        $stmt = $this->db->prepare('SELECT bi.*, p.name as material_name, p.sku as material_sku, p.unit FROM bom_items bi JOIN products p ON bi.material_id = p.id WHERE bi.bom_id = :bom_id ORDER BY bi.sequence');
        $stmt->execute([':bom_id' => $bomId]);
        $bom['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total cost
        $bom['total_cost'] = $this->calculateTotalCost($bomId);
        
        return $bom;
    }
    
    public function addBOMItem(int $bomId, int $materialId, float $quantity, ?float $unitCost = null, ?int $sequence = null): int
    {
        if ($sequence === null) {
            $stmt = $this->db->prepare('SELECT COALESCE(MAX(sequence), 0) + 1 as next_seq FROM bom_items WHERE bom_id = :bom_id');
            $stmt->execute([':bom_id' => $bomId]);
            $sequence = (int)$stmt->fetch(PDO::FETCH_ASSOC)['next_seq'];
        }
        
        $stmt = $this->db->prepare('INSERT INTO bom_items (bom_id, material_id, quantity, unit_cost, sequence, created_at) VALUES (:bom_id, :material_id, :quantity, :unit_cost, :sequence, NOW())');
        $stmt->execute([
            ':bom_id' => $bomId,
            ':material_id' => $materialId,
            ':quantity' => $quantity,
            ':unit_cost' => $unitCost,
            ':sequence' => $sequence
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function updateBOMItem(int $itemId, float $quantity, ?float $unitCost = null): bool
    {
        $stmt = $this->db->prepare('UPDATE bom_items SET quantity = :quantity, unit_cost = :unit_cost, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            ':quantity' => $quantity,
            ':unit_cost' => $unitCost,
            ':id' => $itemId
        ]);
    }
    
    public function deleteBOMItem(int $itemId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM bom_items WHERE id = :id');
        return $stmt->execute([':id' => $itemId]);
    }
    
    public function updateBOMStatus(int $bomId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE boms SET status = :status, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([':status' => $status, ':id' => $bomId]);
    }
    
    // Cost Calculations
    
    public function calculateTotalCost(int $bomId): float
    {
        $stmt = $this->db->prepare('SELECT SUM(quantity * COALESCE(unit_cost, 0)) as total FROM bom_items WHERE bom_id = :bom_id');
        $stmt->execute([':bom_id' => $bomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }
    
    public function calculateJobCost(int $jobId): array
    {
        $cost = [
            'materials' => 0,
            'labor' => 0,
            'overhead' => 0,
            'total' => 0
        ];
        
        // Get job details
        $stmt = $this->db->prepare('SELECT product_id, quantity FROM jobs WHERE id = :id');
        $stmt->execute([':id' => $jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) return $cost;
        
        // Get active BOM for product
        $stmt = $this->db->prepare('SELECT id FROM boms WHERE product_id = :product_id AND status = :status ORDER BY version DESC LIMIT 1');
        $stmt->execute([':product_id' => $job['product_id'], ':status' => 'active']);
        $bom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bom) {
            $unitMaterialCost = $this->calculateTotalCost($bom['id']);
            $cost['materials'] = $unitMaterialCost * $job['quantity'];
        }
        
        // Calculate labor cost from maintenance logs (if tracked)
        $stmt = $this->db->prepare('SELECT SUM(ml.hours_spent) as total_hours FROM maintenance_logs ml JOIN maintenance_tasks mt ON ml.task_id = mt.id WHERE mt.related_job_id = :job_id');
        $stmt->execute([':job_id' => $jobId]);
        $laborRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $laborHours = (float)($laborRow['total_hours'] ?? 0);
        $laborRate = 25.00; // Default rate per hour (configurable)
        $cost['labor'] = $laborHours * $laborRate;
        
        // Overhead (simple calculation: 30% of materials + labor)
        $cost['overhead'] = ($cost['materials'] + $cost['labor']) * 0.30;
        
        $cost['total'] = $cost['materials'] + $cost['labor'] + $cost['overhead'];
        
        return $cost;
    }
    
    // Material Requirements Planning (MRP)
    
    public function calculateMaterialRequirements(int $productId, float $quantity): array
    {
        $requirements = [];
        
        // Get active BOM
        $stmt = $this->db->prepare('SELECT id FROM boms WHERE product_id = :product_id AND status = :status ORDER BY version DESC LIMIT 1');
        $stmt->execute([':product_id' => $productId, ':status' => 'active']);
        $bom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bom) return $requirements;
        
        // Get BOM items
        $stmt = $this->db->prepare('SELECT bi.*, p.name, p.sku, p.unit FROM bom_items bi JOIN products p ON bi.material_id = p.id WHERE bi.bom_id = :bom_id');
        $stmt->execute([':bom_id' => $bom['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $requirements[] = [
                'material_id' => $item['material_id'],
                'material_name' => $item['name'],
                'sku' => $item['sku'],
                'unit' => $item['unit'],
                'unit_quantity' => $item['quantity'],
                'total_quantity' => $item['quantity'] * $quantity,
                'unit_cost' => $item['unit_cost'],
                'total_cost' => ($item['unit_cost'] ?? 0) * $item['quantity'] * $quantity
            ];
        }
        
        return $requirements;
    }
    
    // Accounting Integration Points
    
    public function exportJobCostingData(int $jobId): array
    {
        $cost = $this->calculateJobCost($jobId);
        
        // Get job details
        $stmt = $this->db->prepare('SELECT j.*, o.order_number, p.name as product_name, p.sku FROM jobs j LEFT JOIN orders o ON j.order_id = o.id LEFT JOIN products p ON j.product_id = p.id WHERE j.id = :id');
        $stmt->execute([':id' => $jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'job_id' => $jobId,
            'job_number' => $job['job_number'] ?? '',
            'order_number' => $job['order_number'] ?? '',
            'product' => $job['product_name'] ?? '',
            'sku' => $job['sku'] ?? '',
            'quantity' => $job['quantity'] ?? 0,
            'costs' => $cost,
            'export_date' => date('Y-m-d H:i:s'),
            // Accounting GL codes (configurable per business)
            'gl_codes' => [
                'materials' => '5000',
                'labor' => '5100',
                'overhead' => '5200',
                'wip' => '1300' // Work in Progress inventory
            ]
        ];
    }
    
    public function getFinancialSummary(string $startDate, string $endDate): array
    {
        $summary = [
            'total_jobs' => 0,
            'total_revenue' => 0,
            'total_costs' => 0,
            'total_profit' => 0,
            'cost_breakdown' => [
                'materials' => 0,
                'labor' => 0,
                'overhead' => 0
            ]
        ];
        
        // Get completed jobs in date range
        $stmt = $this->db->prepare('SELECT id FROM jobs WHERE status = :status AND completed_date BETWEEN :start AND :end');
        $stmt->execute([':status' => 'completed', ':start' => $startDate, ':end' => $endDate]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary['total_jobs'] = count($jobs);
        
        foreach ($jobs as $job) {
            $cost = $this->calculateJobCost($job['id']);
            $summary['total_costs'] += $cost['total'];
            $summary['cost_breakdown']['materials'] += $cost['materials'];
            $summary['cost_breakdown']['labor'] += $cost['labor'];
            $summary['cost_breakdown']['overhead'] += $cost['overhead'];
        }
        
        // Revenue calculation (placeholder - would come from order values)
        $summary['total_revenue'] = $summary['total_costs'] * 1.3; // 30% margin example
        $summary['total_profit'] = $summary['total_revenue'] - $summary['total_costs'];
        
        return $summary;
    }
}
