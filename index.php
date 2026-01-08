<?php
/**
 * Main Dashboard
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';
require_once 'config/database.php';

// Check authentication
requireLogin();

$user = getCurrentUser();
$unread_notifications = getUnreadNotificationCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barron Production Management System</title>
    <link rel="stylesheet" href="assets/css/industrial.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <h1 class="navbar-title">BARRON</h1>
                <span class="navbar-subtitle">Production Management</span>
            </div>
            
            <div class="navbar-menu">
                <div class="navbar-user">
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($user['role']); ?></span>
                </div>
                
                <div class="navbar-notifications">
                    <button class="btn-notification" id="notificationBtn">
                        <span class="notification-icon">🔔</span>
                        <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                
                <button class="btn btn-outline btn-sm" onclick="logout()">LOGOUT</button>
            </div>
        </div>
    </nav>
    
    <!-- Main Layout -->
    <div class="layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <?php if (hasPermission('master.view')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">ADMINISTRATION</h3>
                    <a href="modules/master/departments.php" class="nav-item">
                        <span class="nav-icon">🏢</span>
                        <span class="nav-label">Departments</span>
                    </a>
                    <a href="modules/master/employees.php" class="nav-item">
                        <span class="nav-icon">👥</span>
                        <span class="nav-label">Employees</span>
                    </a>
                    <a href="modules/master/machines.php" class="nav-item">
                        <span class="nav-icon">⚙️</span>
                        <span class="nav-label">Machines</span>
                    </a>
                    <a href="modules/master/products.php" class="nav-item">
                        <span class="nav-icon">📦</span>
                        <span class="nav-label">Products</span>
                    </a>
                    <a href="modules/master/roles.php" class="nav-item">
                        <span class="nav-icon">🔐</span>
                        <span class="nav-label">Roles & Permissions</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('planning.view')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">PLANNING</h3>
                    <a href="modules/planning/schedule.php" class="nav-item">
                        <span class="nav-icon">📅</span>
                        <span class="nav-label">Job Scheduling</span>
                    </a>
                    <a href="modules/planning/orders.php" class="nav-item">
                        <span class="nav-icon">📋</span>
                        <span class="nav-label">Orders</span>
                    </a>
                    <a href="modules/planning/capacity.php" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span class="nav-label">Capacity Planning</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('defects.view')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">QUALITY</h3>
                    <a href="modules/defects/internal_rejects.php" class="nav-item">
                        <span class="nav-icon">⚠️</span>
                        <span class="nav-label">Internal Rejects</span>
                    </a>
                    <a href="modules/defects/customer_returns.php" class="nav-item">
                        <span class="nav-icon">↩️</span>
                        <span class="nav-label">Customer Returns</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('sop.view')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">COMPLIANCE</h3>
                    <a href="modules/sop/tickets.php" class="nav-item">
                        <span class="nav-icon">📄</span>
                        <span class="nav-label">SOP Failures</span>
                    </a>
                    <a href="modules/sop/ncr.php" class="nav-item">
                        <span class="nav-icon">📝</span>
                        <span class="nav-label">NCR Reports</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('maintenance.view')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">MAINTENANCE</h3>
                    <a href="modules/maintenance/tickets.php" class="nav-item">
                        <span class="nav-icon">🔧</span>
                        <span class="nav-label">Maintenance Tickets</span>
                    </a>
                    <a href="modules/maintenance/schedule.php" class="nav-item">
                        <span class="nav-icon">🗓️</span>
                        <span class="nav-label">Preventive Schedule</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('finance.view_bom')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">FINANCE</h3>
                    <a href="modules/finance/bom.php" class="nav-item">
                        <span class="nav-icon">💰</span>
                        <span class="nav-label">Bill of Materials</span>
                    </a>
                    <a href="modules/finance/reports.php" class="nav-item">
                        <span class="nav-icon">📈</span>
                        <span class="nav-label">Cost Reports</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('operator.view_jobs')): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title">PRODUCTION</h3>
                    <a href="modules/operator/my_jobs.php" class="nav-item">
                        <span class="nav-icon">⚡</span>
                        <span class="nav-label">My Jobs</span>
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="content-header">
                <h1>Dashboard</h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['name']); ?></p>
            </div>
            
            <div class="dashboard-grid">
                <!-- Quick Stats -->
                <?php if (hasPermission('planning.view')): ?>
                <div class="stat-card">
                    <div class="stat-icon bg-primary">📋</div>
                    <div class="stat-details">
                        <h3 class="stat-value" id="activeOrders">-</h3>
                        <p class="stat-label">Active Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-warning">⚠️</div>
                    <div class="stat-details">
                        <h3 class="stat-value" id="pendingRejects">-</h3>
                        <p class="stat-label">Pending Rejects</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('maintenance.view')): ?>
                <div class="stat-card">
                    <div class="stat-icon bg-danger">🔧</div>
                    <div class="stat-details">
                        <h3 class="stat-value" id="openMaintenance">-</h3>
                        <p class="stat-label">Open Maintenance</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('sop.view')): ?>
                <div class="stat-card">
                    <div class="stat-icon bg-info">📄</div>
                    <div class="stat-details">
                        <h3 class="stat-value" id="openSOP">-</h3>
                        <p class="stat-label">Open SOP Tickets</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">Recent Activity</h3>
                </div>
                <div class="card-body">
                    <div id="recentActivity">
                        <p class="text-muted text-center">Loading...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Notification Panel -->
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button class="btn-close" onclick="closeNotifications()">×</button>
        </div>
        <div class="notification-list" id="notificationList">
            <p class="text-muted text-center">Loading...</p>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
