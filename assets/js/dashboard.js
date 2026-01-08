/**
 * Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadRecentActivity();
    
    // Notification button handler
    const notificationBtn = document.getElementById('notificationBtn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', toggleNotifications);
    }
});

/**
 * Load dashboard statistics
 */
async function loadDashboardStats() {
    try {
        const response = await fetch('api/dashboard/stats.php');
        const data = await response.json();
        
        if (data.success) {
            updateStatCards(data.data);
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

/**
 * Update stat card values
 */
function updateStatCards(stats) {
    const elements = {
        'activeOrders': stats.active_orders || 0,
        'pendingRejects': stats.pending_rejects || 0,
        'openMaintenance': stats.open_maintenance || 0,
        'openSOP': stats.open_sop || 0
    };
    
    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }
}

/**
 * Load recent activity
 */
async function loadRecentActivity() {
    const container = document.getElementById('recentActivity');
    
    try {
        const response = await fetch('api/dashboard/recent_activity.php');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = renderActivityList(data.data);
        } else {
            container.innerHTML = '<p class="text-muted text-center">No recent activity</p>';
        }
    } catch (error) {
        console.error('Error loading recent activity:', error);
        container.innerHTML = '<p class="text-danger text-center">Failed to load activity</p>';
    }
}

/**
 * Render activity list
 */
function renderActivityList(activities) {
    return activities.map(activity => `
        <div class="activity-item" style="padding: var(--spacing-sm) 0; border-bottom: 1px solid var(--color-light);">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <strong>${escapeHtml(activity.action)}</strong>
                    <p style="margin: var(--spacing-xs) 0 0 0; font-size: var(--font-size-small); color: var(--color-grey);">
                        ${escapeHtml(activity.description)}
                    </p>
                </div>
                <span style="font-size: var(--font-size-small); color: var(--color-grey); white-space: nowrap;">
                    ${formatRelativeTime(activity.created_at)}
                </span>
            </div>
        </div>
    `).join('');
}

/**
 * Toggle notification panel
 */
function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    panel.classList.toggle('open');
    
    if (panel.classList.contains('open')) {
        loadNotifications();
    }
}

/**
 * Close notification panel
 */
function closeNotifications() {
    const panel = document.getElementById('notificationPanel');
    panel.classList.remove('open');
}

/**
 * Load notifications
 */
async function loadNotifications() {
    const container = document.getElementById('notificationList');
    
    try {
        const response = await fetch('api/notifications/list.php');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = renderNotificationList(data.data);
        } else {
            container.innerHTML = '<p class="text-muted text-center">No notifications</p>';
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        container.innerHTML = '<p class="text-danger text-center">Failed to load notifications</p>';
    }
}

/**
 * Render notification list
 */
function renderNotificationList(notifications) {
    return notifications.map(notification => `
        <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
             data-id="${notification.id}"
             onclick="markAsRead(${notification.id})">
            <div class="notification-title">${escapeHtml(notification.title)}</div>
            <div class="notification-message">${escapeHtml(notification.message)}</div>
            <div class="notification-time">${formatRelativeTime(notification.created_at)}</div>
        </div>
    `).join('');
}

/**
 * Mark notification as read
 */
async function markAsRead(notificationId) {
    try {
        await fetch('api/notifications/mark_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        });
        
        // Reload notifications
        loadNotifications();
        
        // Update notification count
        updateNotificationCount();
        
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

/**
 * Update notification count badge
 */
async function updateNotificationCount() {
    try {
        const response = await fetch('api/notifications/count.php');
        const data = await response.json();
        
        if (data.success) {
            const badge = document.querySelector('.notification-badge');
            if (data.data.count > 0) {
                if (!badge) {
                    const btn = document.getElementById('notificationBtn');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.data.count;
                    btn.appendChild(newBadge);
                } else {
                    badge.textContent = data.data.count;
                }
            } else if (badge) {
                badge.remove();
            }
        }
    } catch (error) {
        console.error('Error updating notification count:', error);
    }
}

/**
 * Logout function
 */
async function logout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            const response = await fetch('api/auth/logout.php');
            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'login.php';
            }
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = 'login.php';
        }
    }
}

/**
 * Format relative time
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    
    return date.toLocaleDateString();
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Auto-refresh dashboard stats every 30 seconds
 */
setInterval(function() {
    loadDashboardStats();
    updateNotificationCount();
}, 30000);
