<?php
/**
 * Barron Production Management System
 * QC Report Scheduler - Automated Report Generation
 * 
 * This script should be run via cron job or scheduled task
 * Example: Run daily at 6 AM to generate reports
 * 
 * Cron: 0 6 * * * /usr/bin/php /path/to/scripts/qc_report_scheduler.php
 * Windows Task Scheduler: Daily at 6:00 AM
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Defects.php';
require_once __DIR__ . '/../classes/ReplacementTicket.php';

// Logging function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/qc_scheduler.log';
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    echo "[$timestamp] $message" . PHP_EOL;
}

// Report configurations
$reportConfigs = [
    'daily' => [
        'enabled' => true,
        'schedule' => 'daily', // Run every day
        'recipients' => ['qc@barron.com', 'production@barron.com'],
        'departments' => 'all'
    ],
    'weekly' => [
        'enabled' => true,
        'schedule' => 'weekly', // Run on Mondays
        'recipients' => ['manager@barron.com', 'qc@barron.com'],
        'departments' => 'all'
    ],
    'monthly' => [
        'enabled' => true,
        'schedule' => 'monthly', // Run on 1st of month
        'recipients' => ['director@barron.com', 'manager@barron.com'],
        'departments' => 'all'
    ]
];

// Check if report should run today
function shouldRunReport($schedule) {
    $today = date('N'); // 1 (Monday) through 7 (Sunday)
    $dayOfMonth = date('j');
    
    switch ($schedule) {
        case 'daily':
            return true;
        case 'weekly':
            return $today == 1; // Run on Mondays
        case 'monthly':
            return $dayOfMonth == 1; // Run on 1st of month
        default:
            return false;
    }
}

// Generate report data
function generateReportData($reportType) {
    $defects = new Defects();
    $tickets = new ReplacementTicket();
    
    // Calculate date range
    switch ($reportType) {
        case 'daily':
            $date_from = date('Y-m-d', strtotime('-1 day'));
            $date_to = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'weekly':
            $date_from = date('Y-m-d', strtotime('monday last week'));
            $date_to = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'monthly':
            $date_from = date('Y-m-01', strtotime('first day of last month'));
            $date_to = date('Y-m-t', strtotime('last day of last month'));
            break;
        default:
            return null;
    }
    
    logMessage("Generating $reportType report for period: $date_from to $date_to");
    
    // Get data
    $defect_stats = $defects->getStatistics($date_from, $date_to);
    $dept_breakdown = $defects->getByDepartment($date_from, $date_to);
    $trend = $defects->getTrend($reportType === 'monthly' ? 'week' : 'day');
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
    
    return [
        'report_type' => $reportType,
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
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

// Format report as HTML email
function formatReportEmail($reportData) {
    $period = $reportData['period']['label'];
    $kpis = $reportData['kpis'];
    $defects = $reportData['defects'];
    $tickets = $reportData['replacement_tickets'];
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
            .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
            .kpi-card { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: 15px; }
            .kpi-value { font-size: 32px; font-weight: bold; color: #0d6efd; }
            .kpi-label { font-size: 14px; color: #6c757d; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
            th { background: #e9ecef; font-weight: bold; }
            .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
            .badge-danger { background: #dc3545; color: white; }
            .badge-warning { background: #ffc107; color: #000; }
            .badge-success { background: #198754; color: white; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>📊 QC Report - {$reportData['report_type']}</h1>
            <p>Period: {$period}</p>
        </div>
        
        <div style='padding: 20px;'>
            <h2>Key Performance Indicators</h2>
            <div class='kpi-grid'>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$kpis['total_defects']}</div>
                    <div class='kpi-label'>Total Defects</div>
                </div>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$kpis['critical_percentage']}%</div>
                    <div class='kpi-label'>Critical Defects</div>
                </div>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$kpis['resolution_rate']}%</div>
                    <div class='kpi-label'>Resolution Rate</div>
                </div>
            </div>
            
            <h2>Department Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Total Defects</th>
                        <th>Critical</th>
                        <th>High</th>
                        <th>Avg Quantity</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($defects['by_department'] as $dept) {
        $html .= "
                    <tr>
                        <td><strong>{$dept['department_name']}</strong></td>
                        <td>{$dept['total_defects']}</td>
                        <td><span class='badge badge-danger'>{$dept['critical']}</span></td>
                        <td><span class='badge badge-warning'>{$dept['high']}</span></td>
                        <td>" . round($dept['avg_quantity']) . "</td>
                    </tr>";
    }
    
    $html .= "
                </tbody>
            </table>
            
            <h2>Replacement Tickets</h2>
            <table>
                <tr>
                    <th>Total Tickets</th>
                    <td>{$tickets['statistics']['total_tickets']}</td>
                </tr>
                <tr>
                    <th>Pending Approval</th>
                    <td><span class='badge badge-warning'>{$tickets['statistics']['pending_approval']}</span></td>
                </tr>
                <tr>
                    <th>Approved</th>
                    <td><span class='badge badge-success'>{$tickets['statistics']['approved']}</span></td>
                </tr>
                <tr>
                    <th>No Stock</th>
                    <td><span class='badge badge-danger'>{$tickets['statistics']['no_stock']}</span></td>
                </tr>
                <tr>
                    <th>Approval Rate</th>
                    <td>{$kpis['approval_rate']}%</td>
                </tr>
            </table>
        </div>
        
        <div class='footer'>
            <p>Generated: {$reportData['generated_at']}</p>
            <p>Barron Production Management System</p>
        </div>
    </body>
    </html>
    ";
    
    return $html;
}

// Send email (placeholder - will be implemented in Phase 9)
function sendReportEmail($recipients, $subject, $htmlBody) {
    logMessage("Would send email to: " . implode(', ', $recipients));
    logMessage("Subject: $subject");
    
    // TODO: Implement actual email sending in Phase 9 (Notifications module)
    // For now, save report to file
    $filename = __DIR__ . '/../logs/reports/' . date('Y-m-d_His') . '_' . str_replace(' ', '_', $subject) . '.html';
    
    $reportDir = dirname($filename);
    if (!is_dir($reportDir)) {
        mkdir($reportDir, 0755, true);
    }
    
    file_put_contents($filename, $htmlBody);
    logMessage("Report saved to: $filename");
    
    return true;
}

// Main execution
logMessage("=== QC Report Scheduler Started ===");

try {
    $reportsGenerated = 0;
    
    foreach ($reportConfigs as $reportType => $config) {
        if (!$config['enabled']) {
            logMessage("$reportType report is disabled, skipping");
            continue;
        }
        
        if (!shouldRunReport($config['schedule'])) {
            logMessage("$reportType report not scheduled for today, skipping");
            continue;
        }
        
        logMessage("Processing $reportType report...");
        
        // Generate report data
        $reportData = generateReportData($reportType);
        
        if (!$reportData) {
            logMessage("ERROR: Failed to generate $reportType report data");
            continue;
        }
        
        // Format email
        $emailHtml = formatReportEmail($reportData);
        $subject = "QC Report - " . ucfirst($reportType) . " - " . $reportData['period']['label'];
        
        // Send email
        if (sendReportEmail($config['recipients'], $subject, $emailHtml)) {
            logMessage("SUCCESS: $reportType report generated and sent");
            $reportsGenerated++;
        } else {
            logMessage("ERROR: Failed to send $reportType report");
        }
    }
    
    logMessage("=== QC Report Scheduler Completed ===");
    logMessage("Reports generated: $reportsGenerated");
    
} catch (Exception $e) {
    logMessage("CRITICAL ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

exit(0);
