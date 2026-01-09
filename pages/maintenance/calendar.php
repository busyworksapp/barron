<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../../classes/MaintenanceManager.php';
$maint = new MaintenanceManager($GLOBALS['db'] ?? null);

// Get calendar data for current month
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$startDate = "$year-$month-01";
$endDate = date('Y-m-t', strtotime($startDate));

$tasks = $maint->getCalendar($startDate, $endDate);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Calendar</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 24px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 16px; }
        .calendar-header { font-weight: bold; text-align: center; padding: 12px; background: #34495e; color: white; }
        .calendar-day { min-height: 100px; padding: 8px; border: 1px solid #ddd; background: white; border-radius: 4px; }
        .calendar-day.today { background: #ecf0f1; border-color: #3498db; }
        .calendar-day .date { font-weight: bold; margin-bottom: 8px; }
        .task-item { font-size: 12px; padding: 4px; margin: 4px 0; border-radius: 3px; cursor: pointer; }
        .task-item.scheduled { background: #3498db; color: white; }
        .task-item.in_progress { background: #f39c12; color: white; }
        .task-item.completed { background: #27ae60; color: white; }
        .task-item:hover { opacity: 0.8; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Maintenance Calendar - <?=date('F Y', strtotime($startDate))?></h1>
        <a href="/pages/maintenance/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
    
    <div>
        <button class="btn" onclick="navigate(-1)">← Previous Month</button>
        <button class="btn" onclick="navigate(1)">Next Month →</button>
    </div>
    
    <div class="calendar">
        <div class="calendar-header">Sun</div>
        <div class="calendar-header">Mon</div>
        <div class="calendar-header">Tue</div>
        <div class="calendar-header">Wed</div>
        <div class="calendar-header">Thu</div>
        <div class="calendar-header">Fri</div>
        <div class="calendar-header">Sat</div>
        
        <?php
        $firstDay = date('w', strtotime($startDate));
        $daysInMonth = date('t', strtotime($startDate));
        
        // Empty cells before month starts
        for ($i = 0; $i < $firstDay; $i++) {
            echo '<div class="calendar-day"></div>';
        }
        
        // Days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%s-%02d-%02d', $year, $month, $day);
            $isToday = $dateStr === date('Y-m-d');
            $dayClass = $isToday ? 'calendar-day today' : 'calendar-day';
            
            echo "<div class=\"{$dayClass}\">";
            echo "<div class=\"date\">{$day}</div>";
            
            // Show tasks for this day
            foreach ($tasks as $task) {
                if ($task['scheduled_date'] === $dateStr) {
                    $taskTitle = htmlspecialchars(substr($task['title'], 0, 30));
                    echo "<div class=\"task-item {$task['status']}\" onclick=\"location.href='/pages/maintenance/task-details.php?id={$task['id']}'\">";
                    echo "{$taskTitle}";
                    echo "</div>";
                }
            }
            
            echo '</div>';
        }
        ?>
    </div>
</div>

<script>
function navigate(offset) {
    const current = new Date(<?=$year?>, <?=(int)$month - 1?>, 1);
    current.setMonth(current.getMonth() + offset);
    const year = current.getFullYear();
    const month = String(current.getMonth() + 1).padStart(2, '0');
    location.href = `?year=${year}&month=${month}`;
}
</script>
</body>
</html>
