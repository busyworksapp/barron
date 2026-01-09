<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

$pageTitle = 'My Jobs';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Barron Production System</title>
    <link rel="stylesheet" href="../../assets/css/industrial.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/operator.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="page-actions">
                    <button type="button" class="btn btn-secondary" onclick="refreshJobs()">
                        <span class="icon">🔄</span> Refresh
                    </button>
                </div>
            </div>

            <div class="stats-grid" style="margin-bottom: 20px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">📋</div>
                    <div class="stat-details">
                        <div class="stat-value" id="activeJobsCount">0</div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">⏱️</div>
                    <div class="stat-details">
                        <div class="stat-value" id="pendingJobsCount">0</div>
                        <div class="stat-label">Pending Jobs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">✓</div>
                    <div class="stat-details">
                        <div class="stat-value" id="completedTodayCount">0</div>
                        <div class="stat-label">Completed Today</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <select id="statusFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="loadMyJobs()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="jobsContainer">
                        <p class="text-center">Loading your jobs...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Job Details Modal -->
    <div id="jobModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2 id="modalTitle">Job Details</h2>
                <span class="close" onclick="closeJobModal()">&times;</span>
            </div>
            <div id="jobDetailsContent">
                <!-- Job details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../../assets/js/my_jobs.js"></script>
</body>
</html>
