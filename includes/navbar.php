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
                <button class="btn-notification" id="notificationBtn" onclick="window.parent.toggleNotifications ? window.parent.toggleNotifications() : toggleNotifications()">
                    <span class="notification-icon">🔔</span>
                    <?php 
                    $unread_notifications = getUnreadNotificationCount();
                    if ($unread_notifications > 0): 
                    ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <button class="btn btn-outline btn-sm" onclick="logout()">LOGOUT</button>
        </div>
    </div>
</nav>

<script>
function toggleNotifications() {
    // Placeholder - will be implemented per page or globally
    alert('Notifications panel - to be implemented');
}

async function logout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            const basePath = window.location.pathname.includes('/modules/') ? '../../' : '';
            const response = await fetch(basePath + 'api/auth/logout.php');
            const data = await response.json();
            
            if (data.success) {
                window.location.href = basePath + 'login.php';
            }
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = (window.location.pathname.includes('/modules/') ? '../../' : '') + 'login.php';
        }
    }
}
</script>
