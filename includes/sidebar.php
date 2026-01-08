<!-- Sidebar Navigation -->
<aside class="sidebar">
    <nav class="sidebar-nav">
        <?php if (hasPermission('master.view')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">ADMINISTRATION</h3>
            <a href="<?php echo BASE_URL; ?>modules/master/departments.php" class="nav-item">
                <span class="nav-icon">🏢</span>
                <span class="nav-label">Departments</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/master/employees.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span class="nav-label">Employees</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/master/machines.php" class="nav-item">
                <span class="nav-icon">⚙️</span>
                <span class="nav-label">Machines</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/master/products.php" class="nav-item">
                <span class="nav-icon">📦</span>
                <span class="nav-label">Products</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/master/roles.php" class="nav-item">
                <span class="nav-icon">🔐</span>
                <span class="nav-label">Roles & Permissions</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('planning.view')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">PLANNING</h3>
            <a href="<?php echo BASE_URL; ?>modules/planning/orders.php" class="nav-item">
                <span class="nav-icon">�</span>
                <span class="nav-label">Orders</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/planning/schedule.php" class="nav-item">
                <span class="nav-icon">�</span>
                <span class="nav-label">Job Scheduling</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('production.view')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">PRODUCTION</h3>
            <a href="<?php echo BASE_URL; ?>modules/planning/tracking.php" class="nav-item">
                <span class="nav-icon">⚙️</span>
                <span class="nav-label">Production Tracking</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('defects.view')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">QUALITY</h3>
            <a href="<?php echo BASE_URL; ?>modules/defects/internal_rejects.php" class="nav-item">
                <span class="nav-icon">⚠️</span>
                <span class="nav-label">Internal Rejects</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/defects/customer_returns.php" class="nav-item">
                <span class="nav-icon">↩️</span>
                <span class="nav-label">Customer Returns</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('sop.view')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">COMPLIANCE</h3>
            <a href="<?php echo BASE_URL; ?>modules/sop/tickets.php" class="nav-item">
                <span class="nav-icon">📄</span>
                <span class="nav-label">SOP Failures</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/sop/ncr.php" class="nav-item">
                <span class="nav-icon">📝</span>
                <span class="nav-label">NCR Reports</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('maintenance.view')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">MAINTENANCE</h3>
            <a href="<?php echo BASE_URL; ?>modules/maintenance/tickets.php" class="nav-item">
                <span class="nav-icon">🔧</span>
                <span class="nav-label">Maintenance Tickets</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/maintenance/schedule.php" class="nav-item">
                <span class="nav-icon">🗓️</span>
                <span class="nav-label">Preventive Schedule</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('finance.view_bom')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">FINANCE</h3>
            <a href="<?php echo BASE_URL; ?>modules/finance/bom.php" class="nav-item">
                <span class="nav-icon">💰</span>
                <span class="nav-label">Bill of Materials</span>
            </a>
            <a href="<?php echo BASE_URL; ?>modules/finance/reports.php" class="nav-item">
                <span class="nav-icon">📈</span>
                <span class="nav-label">Cost Reports</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (hasPermission('operator.view_jobs')): ?>
        <div class="nav-section">
            <h3 class="nav-section-title">PRODUCTION</h3>
            <a href="<?php echo BASE_URL; ?>modules/operator/my_jobs.php" class="nav-item">
                <span class="nav-icon">⚡</span>
                <span class="nav-label">My Jobs</span>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="nav-section">
            <h3 class="nav-section-title">SYSTEM</h3>
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-item">
                <span class="nav-icon">🏠</span>
                <span class="nav-label">Dashboard</span>
            </a>
        </div>
    </nav>
</aside>

<script>
// Highlight active menu item
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (currentPath.includes(href)) {
            item.classList.add('active');
        }
    });
});
</script>
