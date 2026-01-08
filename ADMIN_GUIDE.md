# Barron Production Management System - Administration Guide

## 📋 Table of Contents

1. [System Administration Overview](#system-administration-overview)
2. [User Management](#user-management)
3. [Role & Permission Management](#role--permission-management)
4. [Database Administration](#database-administration)
5. [System Maintenance](#system-maintenance)
6. [Performance Optimization](#performance-optimization)
7. [Security Management](#security-management)
8. [Backup & Recovery](#backup--recovery)
9. [Monitoring & Alerts](#monitoring--alerts)
10. [Troubleshooting](#troubleshooting)

---

## System Administration Overview

### Administrator Responsibilities

As a system administrator, you are responsible for:

- ✅ User account management (create, modify, disable)
- ✅ Role and permission assignments
- ✅ System performance monitoring
- ✅ Database maintenance and optimization
- ✅ Security audits and updates
- ✅ Backup and recovery procedures
- ✅ System configuration management
- ✅ Log monitoring and analysis
- ✅ Troubleshooting and support

### Access Requirements

**Administrator Account:**
- Username: `admin@barron`
- Default Password: `admin123` (CHANGE IMMEDIATELY)
- Required Permissions: All permissions enabled

### Daily Tasks
- [ ] Review error logs
- [ ] Monitor system performance
- [ ] Check backup status
- [ ] Review user activity
- [ ] Address support tickets

---

## User Management

### Creating New Users

#### Via Employee Module

1. Navigate to **ADMINISTRATION → Employees**
2. Click **Add Employee**
3. Fill in required fields:
   - First Name, Last Name
   - Email, Phone
   - Employee Number
   - Hire Date
   - Department(s)
4. System auto-generates username: `firstname@barron`
5. Set initial password (user should change on first login)
6. Assign roles for system access
7. Save employee record

#### Direct Database Access (Emergency Only)

```sql
-- Create user account
INSERT INTO users (username, password, email, first_name, last_name, status)
VALUES ('john@barron', '$2y$12$hashedpassword', 'john@example.com', 'John', 'Doe', 'active');

-- Get the user ID
SET @user_id = LAST_INSERT_ID();

-- Assign role (example: role_id 2 = Manager)
INSERT INTO user_roles (user_id, role_id)
VALUES (@user_id, 2);
```

### Modifying User Accounts

#### Change User Status
- **Active** - User can log in
- **Inactive** - User cannot log in (soft delete)
- **Terminated** - Employment ended (audit trail preserved)

#### Reset Password

```sql
-- Generate new bcrypt hash (cost: 12)
-- Use PHP to generate: password_hash('newpassword', PASSWORD_BCRYPT, ['cost' => 12])

UPDATE users 
SET password = '$2y$12$newhash...'
WHERE username = 'john@barron';
```

#### Change Username (Rare)

```sql
UPDATE users 
SET username = 'newemail@barron'
WHERE id = 123;
```

### Disabling User Accounts

**Temporary Disable:**
```sql
UPDATE users SET status = 'inactive' WHERE username = 'john@barron';
```

**Permanent Termination:**
```sql
UPDATE employees SET status = 'terminated', termination_date = CURDATE() 
WHERE id = (SELECT employee_id FROM users WHERE username = 'john@barron');

UPDATE users SET status = 'inactive' WHERE username = 'john@barron';
```

### Bulk User Operations

#### Export User List
```sql
SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.status,
       GROUP_CONCAT(r.name) as roles,
       u.created_at, u.last_login
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id
LEFT JOIN roles r ON ur.role_id = r.id
GROUP BY u.id
ORDER BY u.created_at DESC;
```

#### Find Inactive Users (30+ days)
```sql
SELECT username, email, last_login, DATEDIFF(NOW(), last_login) as days_inactive
FROM users
WHERE status = 'active' AND last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY last_login ASC;
```

---

## Role & Permission Management

### Understanding Permissions

**Current Permissions (17 total):**

| Permission | Description | Modules |
|------------|-------------|---------|
| `master.view` | View master data | Departments, Employees, Machines, Products |
| `master.edit` | Edit master data | All master data modules |
| `planning.view` | View job planning | Orders, Scheduling |
| `planning.edit` | Edit job planning | Orders, Scheduling |
| `production.view` | View production | Production Tracking |
| `production.edit` | Log production | Production Tracking |
| `defects.view` | View defects | Internal Rejects, Returns |
| `defects.edit` | Edit defects | Internal Rejects, Returns |
| `defects.approve` | Approve rejects | Internal Rejects approval |
| `sop.view` | View compliance | SOP Failures, NCRs |
| `sop.edit` | Edit compliance | SOP Failures, NCRs |
| `maintenance.view` | View maintenance | Tickets, PM Schedules |
| `maintenance.edit` | Edit maintenance | Tickets, PM Schedules |
| `finance.view_bom` | View BOMs | Bill of Materials |
| `finance.edit_bom` | Edit BOMs | Bill of Materials |
| `operator.view_jobs` | View assigned jobs | Operator Dashboard |
| `reports.view` | View reports | Reports (future) |

### Creating Custom Roles

#### Example: Quality Inspector Role

```sql
-- 1. Create role
INSERT INTO roles (name, description) VALUES ('Quality Inspector', 'Can view and edit quality-related data');

-- 2. Get role ID
SET @role_id = LAST_INSERT_ID();

-- 3. Assign permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT @role_id, id FROM permissions 
WHERE name IN ('defects.view', 'defects.edit', 'production.view', 'sop.view');
```

#### Example: Maintenance Technician Role

```sql
INSERT INTO roles (name, description) VALUES ('Maintenance Technician', 'Can manage maintenance tickets');

SET @role_id = LAST_INSERT_ID();

INSERT INTO role_permissions (role_id, permission_id)
SELECT @role_id, id FROM permissions 
WHERE name IN ('maintenance.view', 'maintenance.edit', 'master.view');
```

### Assigning Roles to Users

```sql
-- Single role assignment
INSERT INTO user_roles (user_id, role_id)
VALUES (123, 5);

-- Multiple roles for one user
INSERT INTO user_roles (user_id, role_id)
VALUES 
    (123, 2),  -- Manager role
    (123, 5);  -- Quality Inspector role
```

### Removing Role Assignments

```sql
-- Remove specific role from user
DELETE FROM user_roles WHERE user_id = 123 AND role_id = 5;

-- Remove all roles from user
DELETE FROM user_roles WHERE user_id = 123;
```

### Auditing Role Usage

```sql
-- Count users per role
SELECT r.name, COUNT(ur.user_id) as user_count
FROM roles r
LEFT JOIN user_roles ur ON r.id = ur.role_id
GROUP BY r.id
ORDER BY user_count DESC;

-- Find users with multiple roles
SELECT u.username, COUNT(ur.role_id) as role_count, GROUP_CONCAT(r.name) as roles
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
GROUP BY u.id
HAVING COUNT(ur.role_id) > 1;
```

---

## Database Administration

### Daily Maintenance

#### Check Database Size
```sql
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'railway'
GROUP BY table_schema;
```

#### Check Table Sizes
```sql
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
    table_rows AS 'Rows'
FROM information_schema.tables
WHERE table_schema = 'railway'
ORDER BY (data_length + index_length) DESC;
```

#### Optimize Tables (Monthly)
```sql
OPTIMIZE TABLE users, orders, jobs, production_logs, internal_rejects, 
    customer_returns, maintenance_tickets, bom;
```

### Index Management

#### Check Index Usage
```sql
SELECT 
    table_name,
    index_name,
    cardinality
FROM information_schema.statistics
WHERE table_schema = 'railway'
ORDER BY cardinality DESC;
```

#### Rebuild Indexes (if needed)
```sql
ALTER TABLE orders DROP INDEX idx_customer, ADD INDEX idx_customer (customer_name);
```

### Query Performance

#### Find Slow Queries
```sql
-- Enable slow query log in MySQL config
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2; -- Queries taking > 2 seconds

-- Review slow query log
SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;
```

#### Analyze Query Execution
```sql
EXPLAIN SELECT o.*, oi.product_id, oi.quantity 
FROM orders o 
INNER JOIN order_items oi ON o.id = oi.order_id 
WHERE o.status = 'pending';
```

### Data Cleanup

#### Archive Old Activity Logs (Older than 90 days)
```sql
-- Create archive table if not exists
CREATE TABLE activity_logs_archive LIKE activity_logs;

-- Move old records
INSERT INTO activity_logs_archive 
SELECT * FROM activity_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete from main table
DELETE FROM activity_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

#### Clean Up Obsolete BOMs (Manual approval)
```sql
-- Review obsolete BOMs older than 1 year
SELECT * FROM bom 
WHERE status = 'obsolete' 
AND updated_at < DATE_SUB(NOW(), INTERVAL 365 DAY);

-- Archive if approved by management
-- (Manual process, do not automate)
```

---

## System Maintenance

### Redis Maintenance

#### Check Redis Memory Usage
```bash
redis-cli INFO memory
```

#### Clear All Sessions (Maintenance Mode)
```bash
redis-cli FLUSHDB
```

#### Monitor Redis Performance
```bash
redis-cli --latency
redis-cli --stat
```

### PHP Configuration

#### Recommended php.ini Settings (Production)
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 12M
session.gc_maxlifetime = 1800
```

### Application Cache Clearing

If you implement file-based caching:
```bash
# Clear application cache
rm -rf /var/www/barron-production/cache/*
```

### Log Rotation

#### Configure logrotate for PHP logs
```bash
# /etc/logrotate.d/barron-production
/var/log/php/error.log {
    weekly
    rotate 4
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

---

## Performance Optimization

### Database Optimization

#### Add Indexes for Common Queries
```sql
-- Orders by customer
CREATE INDEX idx_orders_customer ON orders(customer_name, order_date);

-- Jobs by status and date
CREATE INDEX idx_jobs_status_date ON jobs(status, start_date);

-- Production logs by job
CREATE INDEX idx_production_job ON production_logs(job_id, production_date);

-- Maintenance tickets by machine
CREATE INDEX idx_maintenance_machine ON maintenance_tickets(machine_id, status);
```

#### Query Caching (MySQL 5.7)
```sql
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;
```

### Application Optimization

#### Enable OPcache (php.ini)
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

#### Redis Configuration Tuning
```conf
# redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### Monitoring Query Performance

```sql
-- Show running queries
SHOW FULL PROCESSLIST;

-- Kill long-running query
KILL QUERY 123;
```

---

## Security Management

### Security Audit Checklist

#### Weekly Security Tasks
- [ ] Review failed login attempts
- [ ] Check for suspicious activity in logs
- [ ] Verify no unauthorized users created
- [ ] Review permission changes
- [ ] Check for SQL injection attempts in logs
- [ ] Verify SSL certificate validity

#### Monthly Security Tasks
- [ ] Update PHP to latest version
- [ ] Update MySQL to latest version
- [ ] Review and update firewall rules
- [ ] Audit user accounts (disable inactive)
- [ ] Review role assignments
- [ ] Change admin password
- [ ] Test backup restoration

### Failed Login Monitoring

```sql
-- Add failed_login_attempts table
CREATE TABLE failed_login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_ip (ip_address)
);

-- Query failed attempts
SELECT username, ip_address, COUNT(*) as attempts, MAX(attempted_at) as last_attempt
FROM failed_login_attempts
WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY username, ip_address
HAVING attempts > 5
ORDER BY attempts DESC;
```

### Password Policy

**Current Requirements:**
- Minimum 8 characters (recommended: 12+)
- Bcrypt hashing with cost 12
- No password expiration (manual enforcement recommended)

**Recommended Policy:**
- Force password change every 90 days
- Minimum 12 characters
- Require mix of uppercase, lowercase, numbers, symbols

### SSL/TLS Configuration

Ensure HTTPS is enforced:
```apache
# Apache .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Backup & Recovery

### Backup Strategy

#### Daily Database Backup
```bash
#!/bin/bash
# /usr/local/bin/barron-backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/barron-production"
DB_NAME="railway"
DB_USER="root"
DB_PASS="your_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Remove backups older than 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

# Log completion
echo "$(date): Backup completed - db_$DATE.sql.gz" >> /var/log/barron-backup.log
```

#### Schedule with Cron
```bash
# Run daily at 2:00 AM
0 2 * * * /usr/local/bin/barron-backup.sh
```

### Restore Procedures

#### Full Database Restore
```bash
# Decompress and restore
gunzip < /backups/barron-production/db_20260108_020000.sql.gz | mysql -u root -p railway
```

#### Single Table Restore
```bash
# Extract specific table
gunzip < backup.sql.gz | sed -n '/CREATE TABLE `orders`/,/UNLOCK TABLES/p' | mysql -u root -p railway
```

### Backup Verification

```bash
#!/bin/bash
# Test backup integrity weekly

BACKUP_FILE="/backups/barron-production/db_20260108_020000.sql.gz"

# Check file exists and is not empty
if [ -s "$BACKUP_FILE" ]; then
    # Test gzip integrity
    gunzip -t $BACKUP_FILE
    if [ $? -eq 0 ]; then
        echo "Backup integrity: OK"
    else
        echo "ERROR: Backup file corrupted!"
        # Send alert email
    fi
else
    echo "ERROR: Backup file missing or empty!"
fi
```

---

## Monitoring & Alerts

### System Monitoring

#### CPU & Memory Usage
```bash
# Real-time monitoring
top
htop

# Check specific process
ps aux | grep php-fpm
ps aux | grep mysql
```

#### Disk Space Monitoring
```bash
# Check disk usage
df -h

# Find large files
du -h /var/www/barron-production | sort -rh | head -20
```

### Application Monitoring

#### Error Log Monitoring
```bash
# Watch error log in real-time
tail -f /var/log/php/error.log

# Count errors today
grep "$(date +%Y-%m-%d)" /var/log/php/error.log | wc -l

# Find most common errors
grep "$(date +%Y-%m-%d)" /var/log/php/error.log | cut -d']' -f3 | sort | uniq -c | sort -rn
```

#### Activity Monitoring
```sql
-- Active users (last 24 hours)
SELECT COUNT(DISTINCT user_id) as active_users
FROM activity_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Most common actions today
SELECT action, COUNT(*) as count
FROM activity_logs
WHERE DATE(created_at) = CURDATE()
GROUP BY action
ORDER BY count DESC;

-- User activity summary
SELECT u.username, COUNT(al.id) as actions, MAX(al.created_at) as last_activity
FROM users u
INNER JOIN activity_logs al ON u.id = al.user_id
WHERE al.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY u.id
ORDER BY actions DESC;
```

### Alert Configuration

#### Email Alerts for Critical Issues
```php
// /usr/local/bin/send_alert.php
<?php
function sendAlert($subject, $message) {
    $to = 'admin@barron.com';
    $headers = 'From: alerts@barron.com';
    mail($to, $subject, $message, $headers);
}

// Example: Disk space alert
$diskUsage = disk_free_space('/') / disk_total_space('/');
if ($diskUsage < 0.1) { // Less than 10% free
    sendAlert('CRITICAL: Low Disk Space', "Disk usage: " . (1-$diskUsage)*100 . "%");
}
?>
```

---

## Troubleshooting

### Common Issues & Solutions

#### Issue: Users Cannot Login

**Symptoms:** Login fails with correct credentials

**Diagnosis:**
```sql
-- Check user exists and is active
SELECT id, username, status FROM users WHERE username = 'john@barron';

-- Check Redis connection
```
```bash
redis-cli PING
```

**Solutions:**
1. Verify user status is 'active'
2. Check Redis is running
3. Clear user's session in Redis
4. Verify password hash is correct
5. Check PHP session configuration

---

#### Issue: Slow Page Load

**Symptoms:** Pages take > 5 seconds to load

**Diagnosis:**
```sql
-- Check slow queries
SHOW FULL PROCESSLIST;

-- Check table sizes
SELECT table_name, table_rows FROM information_schema.tables 
WHERE table_schema = 'railway' ORDER BY table_rows DESC;
```

**Solutions:**
1. Optimize large tables
2. Add missing indexes
3. Clear Redis cache
4. Restart PHP-FPM
5. Check server resources (CPU/RAM)

---

#### Issue: Database Connection Failed

**Symptoms:** Error: "Could not connect to database"

**Diagnosis:**
```bash
# Test MySQL connection
mysql -h yamanote.proxy.rlwy.net -P 39713 -u root -p

# Check MySQL is running
systemctl status mysql
```

**Solutions:**
1. Verify MySQL is running
2. Check credentials in config.php
3. Verify firewall allows connection
4. Check MySQL max_connections setting
5. Restart MySQL service

---

#### Issue: Permissions Not Working

**Symptoms:** User sees "Permission Denied" incorrectly

**Diagnosis:**
```sql
-- Check user roles
SELECT u.username, r.name as role
FROM users u
INNER JOIN user_roles ur ON u.id = ur.user_id
INNER JOIN roles r ON ur.role_id = r.id
WHERE u.username = 'john@barron';

-- Check role permissions
SELECT r.name as role, p.name as permission
FROM roles r
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE r.id = 2;
```

**Solutions:**
1. Verify user has correct role assigned
2. Verify role has required permission
3. Clear Redis session cache
4. Log out and log back in
5. Check Auth class logic

---

### Emergency Procedures

#### System Down (Complete Outage)

1. **Immediate Assessment**
   ```bash
   # Check if services are running
   systemctl status apache2
   systemctl status mysql
   systemctl status redis
   ```

2. **Restart Services**
   ```bash
   systemctl restart apache2
   systemctl restart mysql
   systemctl restart redis
   ```

3. **Check Logs**
   ```bash
   tail -100 /var/log/apache2/error.log
   tail -100 /var/log/mysql/error.log
   tail -100 /var/log/redis/redis-server.log
   ```

4. **Notify Users**
   - Post maintenance message
   - Estimate restoration time
   - Keep stakeholders informed

---

## Appendix

### Useful SQL Queries

#### System Statistics
```sql
-- Total users
SELECT COUNT(*) FROM users WHERE status = 'active';

-- Total orders this month
SELECT COUNT(*) FROM orders WHERE MONTH(order_date) = MONTH(CURDATE());

-- Total production this month
SELECT SUM(quantity_produced) FROM production_logs WHERE MONTH(production_date) = MONTH(CURDATE());

-- Open maintenance tickets
SELECT COUNT(*) FROM maintenance_tickets WHERE status IN ('open', 'assigned', 'in_progress');
```

#### Data Integrity Checks
```sql
-- Orphaned order items (orders deleted but items remain)
SELECT * FROM order_items WHERE order_id NOT IN (SELECT id FROM orders);

-- Jobs without orders
SELECT * FROM jobs WHERE order_id NOT IN (SELECT id FROM orders);

-- Users without roles
SELECT * FROM users WHERE id NOT IN (SELECT user_id FROM user_roles);
```

---

**Last Updated:** January 8, 2026  
**Version:** 1.1  
**Barron (Pty) Ltd - Production Management System**
