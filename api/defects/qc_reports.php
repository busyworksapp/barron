<?php
/**
 * Barron Production Management System
 * QC Reports API Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Defects.php';
require_once __DIR__ . '/../../classes/ReplacementTicket.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

// Initialize
$defects = new Defects();
$tickets = new ReplacementTicket();
$response = ['success' => false];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Check view permission
    if (!checkPermission('defects.view')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Generate QC reports
    if ($method === 'GET') {
        $report_type = $_GET['type'] ?? 'weekly';
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        
        // Calculate date range based on report type
        if (!$date_from || !$date_to) {
            switch ($report_type) {
                case 'daily':
                    $date_from = date('Y-m-d');
                    $date_to = date('Y-m-d');
                    break;
                case 'weekly':
                    $date_from = date('Y-m-d', strtotime('monday this week'));
                    $date_to = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'monthly':
                    $date_from = date('Y-m-01');
                    $date_to = date('Y-m-t');
                    break;
                case 'custom':
                    if (!$date_from || !$date_to) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Date range required for custom report']);
                        exit;
                    }
                    break;
                default:
                    $date_from = date('Y-m-d', strtotime('monday this week'));
                    $date_to = date('Y-m-d', strtotime('sunday this week'));
            }
        }
        
        // Get defect statistics
        $defect_stats = $defects->getStatistics($date_from, $date_to);
        
        // Get department breakdown
        $dept_breakdown = $defects->getByDepartment($date_from, $date_to);
        
        // Get trend data
        $trend = $defects->getTrend($report_type === 'monthly' ? 'week' : 'day');
        
        // Get replacement ticket statistics
        $ticket_stats = $tickets->getStatistics($date_from, $date_to);
        
        // Calculate KPIs
        $total_defects = $defect_stats['total_defects'];
        $critical_percentage = $total_defects > 0 
            ? round(($defect_stats['critical_defects'] / $total_defects) * 100, 2) 
            : 0;
        
        $resolution_rate = $total_defects > 0 
            ? round((($defect_stats['resolved_defects'] + $defect_stats['closed_defects']) / $total_defects) * 100, 2) 
            : 0;
        
        $approval_rate = $ticket_stats['total_tickets'] > 0 
            ? round(($ticket_stats['approved'] / $ticket_stats['total_tickets']) * 100, 2) 
            : 0;
        
        $response = [
            'success' => true,
            'report' => [
                'type' => $report_type,
                'period' => [
                    'from' => $date_from,
                    'to' => $date_to,
                    'label' => date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to))
                ],
                'defects' => [
                    'statistics' => $defect_stats,
                    'by_department' => $dept_breakdown,
                    'trend' => $trend
                ],
                'replacement_tickets' => [
                    'statistics' => $ticket_stats
                ],
                'kpis' => [
                    'total_defects' => $total_defects,
                    'critical_percentage' => $critical_percentage,
                    'resolution_rate' => $resolution_rate,
                    'tickets_pending_approval' => $ticket_stats['pending_approval'],
                    'approval_rate' => $approval_rate,
                    'no_stock_count' => $ticket_stats['no_stock']
                ],
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $_SESSION['user_name'] ?? 'System'
            ]
        ];
    }
    
    // POST - Schedule automated report
    elseif ($method === 'POST') {
        // Check admin permission for scheduling
        if (!checkPermission('admin.settings')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied - Admin access required']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // This would save the schedule configuration
        // For now, return success with planned implementation
        $response = [
            'success' => true,
            'message' => 'Report scheduling will be implemented in Phase 9 (Notifications module)',
            'configuration' => [
                'frequency' => $input['frequency'] ?? 'weekly',
                'recipients' => $input['recipients'] ?? [],
                'format' => $input['format'] ?? 'pdf'
            ]
        ];
    }
    
    else {
        http_response_code(405);
        $response = ['success' => false, 'message' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
