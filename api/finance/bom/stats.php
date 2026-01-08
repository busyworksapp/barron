<?php
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('finance.view_bom')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Active BOMs count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bom WHERE status = 'active'");
    $activeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Approved BOMs count (active status means approved)
    $approvedCount = $activeCount;
    
    // Draft BOMs count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bom WHERE status = 'draft'");
    $draftCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Average BOM cost
    $stmt = $pdo->query("SELECT AVG(total_cost) as avg_cost FROM bom WHERE status = 'active'");
    $avgCost = $stmt->fetch(PDO::FETCH_ASSOC)['avg_cost'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'active_count' => (int)$activeCount,
            'approved_count' => (int)$approvedCount,
            'draft_count' => (int)$draftCount,
            'avg_cost' => number_format($avgCost, 2, '.', '')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
