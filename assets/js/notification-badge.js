/**
 * Notification Badge Component
 * Shows unread notification count and provides dropdown preview
 * Usage: Include this JS file in your layout, ensure #notificationBadge exists in DOM
 */

class NotificationBadge {
    constructor(containerId = 'notificationBadge') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.warn('NotificationBadge: container not found');
            return;
        }
        this.unreadCount = 0;
        this.notifications = [];
        this.init();
    }
    
    init() {
        this.render();
        this.fetchUnreadCount();
        // Poll for new notifications every 30 seconds
        setInterval(() => this.fetchUnreadCount(), 30000);
    }
    
    render() {
        this.container.innerHTML = `
            <div class="notification-badge">
                <button class="badge-button" id="notificationBadgeBtn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="badge-count" id="notificationBadgeCount" style="display:none;">0</span>
                </button>
                <div class="badge-dropdown" id="notificationBadgeDropdown" style="display:none;">
                    <div class="dropdown-header">
                        <strong>Notifications</strong>
                        <a href="/pages/notifications/center.php" class="view-all">View All</a>
                    </div>
                    <div class="dropdown-list" id="notificationBadgeList">Loading...</div>
                </div>
            </div>
        `;
        
        document.getElementById('notificationBadgeBtn').addEventListener('click', () => this.toggleDropdown());
        
        // Add styles if not already present
        if (!document.getElementById('notificationBadgeStyles')) {
            const style = document.createElement('style');
            style.id = 'notificationBadgeStyles';
            style.textContent = `
                .notification-badge { position: relative; display: inline-block; }
                .badge-button { background: transparent; border: none; cursor: pointer; position: relative; padding: 8px; }
                .badge-count { position: absolute; top: 4px; right: 4px; background: #e74c3c; color: white; border-radius: 10px; padding: 2px 6px; font-size: 10px; font-weight: bold; }
                .badge-dropdown { position: absolute; top: 40px; right: 0; width: 320px; max-height: 400px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; overflow: hidden; }
                .dropdown-header { padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
                .dropdown-header .view-all { font-size: 12px; color: #3498db; text-decoration: none; }
                .dropdown-list { max-height: 320px; overflow-y: auto; }
                .dropdown-item { padding: 12px; border-bottom: 1px solid #f5f5f5; cursor: pointer; }
                .dropdown-item:hover { background: #f9f9f9; }
                .dropdown-item.unread { background: #ecf0f1; }
                .dropdown-item .title { font-weight: bold; font-size: 14px; margin-bottom: 4px; }
                .dropdown-item .message { font-size: 12px; color: #666; }
                .dropdown-item .time { font-size: 10px; color: #999; margin-top: 4px; }
                .dropdown-empty { padding: 40px; text-align: center; color: #999; }
            `;
            document.head.appendChild(style);
        }
    }
    
    async fetchUnreadCount() {
        try {
            const res = await fetch('/api/notifications/notifications.php?unread_count=1');
            const json = await res.json();
            if (json.success) {
                this.updateBadgeCount(json.count);
            }
        } catch (err) {
            console.error('Failed to fetch unread count', err);
        }
    }
    
    updateBadgeCount(count) {
        this.unreadCount = count;
        const badge = document.getElementById('notificationBadgeCount');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }
    
    async toggleDropdown() {
        const dropdown = document.getElementById('notificationBadgeDropdown');
        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            await this.loadNotifications();
        } else {
            dropdown.style.display = 'none';
        }
    }
    
    async loadNotifications() {
        const listEl = document.getElementById('notificationBadgeList');
        listEl.innerHTML = 'Loading...';
        
        try {
            const res = await fetch('/api/notifications/notifications.php?limit=10');
            const json = await res.json();
            if (json.success) {
                this.notifications = json.data;
                this.renderNotifications();
            }
        } catch (err) {
            listEl.innerHTML = '<div class="dropdown-empty">Failed to load</div>';
        }
    }
    
    renderNotifications() {
        const listEl = document.getElementById('notificationBadgeList');
        if (this.notifications.length === 0) {
            listEl.innerHTML = '<div class="dropdown-empty">No notifications</div>';
            return;
        }
        
        listEl.innerHTML = this.notifications.map(n => `
            <div class="dropdown-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}">
                <div class="title">${this.escapeHtml(n.title)}</div>
                <div class="message">${this.escapeHtml(n.message)}</div>
                <div class="time">${this.escapeHtml(n.created_at)}</div>
            </div>
        `).join('');
        
        // Add click handlers to mark as read
        listEl.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => this.markAsRead(item.dataset.id));
        });
    }
    
    async markAsRead(id) {
        try {
            await fetch('/api/notifications/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({mark_read: true, id: id})
            });
            this.fetchUnreadCount();
            this.loadNotifications();
        } catch (err) {
            console.error('Failed to mark as read', err);
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Auto-initialize if DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new NotificationBadge());
} else {
    new NotificationBadge();
}
