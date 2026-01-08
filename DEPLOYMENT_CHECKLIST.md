# Barron Production Management System - Deployment Checklist

## Pre-Deployment Checklist

### ✅ Environment Setup

#### Server Requirements
- [ ] PHP 8.0 or higher installed
- [ ] MySQL 8.0 or higher installed
- [ ] Redis 6.0 or higher installed
- [ ] Apache/Nginx web server configured
- [ ] mod_rewrite enabled (Apache) or equivalent (Nginx)
- [ ] PHP extensions: PDO, PDO_MySQL, Redis, mbstring, JSON
- [ ] SSL certificate installed (recommended)
- [ ] Firewall configured (ports 80, 443, 3306, 6379)

#### File System
- [ ] Application directory created: `/var/www/barron-production/`
- [ ] Permissions set: `chmod 755` on directories
- [ ] Permissions set: `chmod 644` on files
- [ ] Special permissions: `chmod 600 config/config.php`
- [ ] Upload directory created (if needed): `/uploads/`
- [ ] Upload permissions: `chmod 755` with proper ownership
- [ ] Logs directory created: `/logs/`
- [ ] Log permissions: `chmod 755` with write access

---

### ✅ Database Setup

#### MySQL Configuration
- [ ] Database created: `railway` (or custom name)
- [ ] User created with appropriate privileges
- [ ] Password set (strong, 16+ characters)
- [ ] Grant ALL PRIVILEGES to application user
- [ ] Test connection from application server
- [ ] Character set: utf8mb4
- [ ] Collation: utf8mb4_unicode_ci

#### Schema Import
- [ ] Import `database/schema_master.sql`
- [ ] Import `database/schema_planning.sql`
- [ ] Import `database/schema_defects.sql`
- [ ] Import `database/schema_sop.sql`
- [ ] Import `database/schema_maintenance.sql`
- [ ] Import `database/schema_bom.sql`
- [ ] Verify all 22+ tables created
- [ ] Verify indexes created
- [ ] Verify foreign keys created

#### Initial Data
- [ ] Create default admin user
- [ ] Create default roles
- [ ] Create default permissions
- [ ] Assign admin role to admin user
- [ ] Test admin login

---

### ✅ Redis Configuration

#### Redis Setup
- [ ] Redis server running
- [ ] Password configured
- [ ] Persistence enabled (RDB or AOF)
- [ ] Memory limit set appropriately
- [ ] Max connections configured
- [ ] Test connection from application server

#### Session Configuration
- [ ] Session handler set to Redis
- [ ] Session timeout: 30 minutes
- [ ] Test session creation/destruction

---

### ✅ Application Configuration

#### config/config.php
- [ ] Update `DB_HOST` with production host
- [ ] Update `DB_NAME` with production database
- [ ] Update `DB_USER` with production user
- [ ] Update `DB_PASS` with production password
- [ ] Update `REDIS_HOST` with production Redis host
- [ ] Update `REDIS_PORT` with production Redis port
- [ ] Update `REDIS_PASS` with production Redis password
- [ ] Update `BASE_URL` with production URL
- [ ] Set `ENVIRONMENT` to 'production'
- [ ] Disable `display_errors`
- [ ] Enable `error_logging`
- [ ] Set `log_errors` to file
- [ ] Configure `error_log` path

#### Security Settings
- [ ] Change default admin password
- [ ] Remove development users
- [ ] Set secure session cookie parameters
- [ ] Enable HTTPS redirect
- [ ] Configure CORS if needed
- [ ] Set secure headers (X-Frame-Options, X-XSS-Protection, etc.)
- [ ] Implement rate limiting (optional)
- [ ] Configure CSRF protection (optional)

---

### ✅ Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security Headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set X-Content-Type-Options "nosniff"

# HTTPS Redirect
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Nginx (server block)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/barron-production;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # HTTPS Redirect
    return 301 https://$server_name$request_uri;
}
```

---

### ✅ Testing

#### Functional Testing
- [ ] Test login with admin credentials
- [ ] Test logout functionality
- [ ] Test session timeout (wait 30 minutes)
- [ ] Test invalid login attempts
- [ ] Navigate to all 16 module pages
- [ ] Test permission-based access control
- [ ] Create test records in each module
- [ ] Edit test records
- [ ] View test records
- [ ] Delete test records (if applicable)

#### Module Testing

**Master Data**
- [ ] Create department with stages
- [ ] Create employee with role assignment
- [ ] Create machine with specifications
- [ ] Create product with SKU
- [ ] Test search functionality
- [ ] Test filtering

**Job Planning**
- [ ] Create multi-item order
- [ ] Schedule job from order
- [ ] Log production progress
- [ ] Verify progress calculation
- [ ] Test capacity validation

**Defects**
- [ ] Log internal reject
- [ ] Test approval workflow
- [ ] Create customer return (RMA)
- [ ] Test resolution workflow
- [ ] Verify rate calculations

**Compliance**
- [ ] Create SOP failure ticket
- [ ] Create NCR report
- [ ] Test CAPA workflow
- [ ] Verify overdue alerts

**Maintenance**
- [ ] Create maintenance ticket
- [ ] Test machine status update
- [ ] Create PM schedule
- [ ] Test mark-as-performed
- [ ] Verify next-due calculation

**Finance**
- [ ] Create BOM with components
- [ ] Test cost calculation
- [ ] Update BOM
- [ ] Test version management

#### API Testing
- [ ] Test all 74 API endpoints
- [ ] Verify authentication on all endpoints
- [ ] Test permission checks
- [ ] Verify error responses
- [ ] Test transaction rollback on errors

#### Performance Testing
- [ ] Load test with 100 concurrent users
- [ ] Verify response time < 2 seconds
- [ ] Monitor database query performance
- [ ] Check Redis memory usage
- [ ] Monitor PHP memory usage

#### Security Testing
- [ ] Test SQL injection attempts
- [ ] Test XSS attempts
- [ ] Test CSRF attacks (if protection enabled)
- [ ] Test unauthorized access attempts
- [ ] Verify session hijacking prevention
- [ ] Test password strength requirements

---

### ✅ Monitoring & Logging

#### Application Logs
- [ ] Configure PHP error log: `/var/log/php/error.log`
- [ ] Configure application log: `/var/www/barron-production/logs/app.log`
- [ ] Set log rotation (logrotate)
- [ ] Monitor log size
- [ ] Set up log aggregation (optional)

#### Database Logs
- [ ] Enable MySQL slow query log
- [ ] Monitor query performance
- [ ] Set up database backups (daily)
- [ ] Test backup restoration

#### Redis Logs
- [ ] Monitor Redis memory usage
- [ ] Configure Redis persistence
- [ ] Test Redis failover (if clustered)

#### System Monitoring
- [ ] Set up server monitoring (CPU, RAM, Disk)
- [ ] Configure alerts for high resource usage
- [ ] Monitor network traffic
- [ ] Set up uptime monitoring

---

### ✅ Backup & Recovery

#### Backup Strategy
- [ ] Database backup: Daily at 2:00 AM
- [ ] File system backup: Weekly
- [ ] Redis backup: Daily (if persistence enabled)
- [ ] Backup retention: 30 days
- [ ] Offsite backup storage configured

#### Backup Scripts
```bash
#!/bin/bash
# Daily Database Backup
mysqldump -u root -p railway > /backups/db_$(date +%Y%m%d).sql
gzip /backups/db_$(date +%Y%m%d).sql

# Clean old backups (30 days)
find /backups -name "db_*.sql.gz" -mtime +30 -delete
```

#### Recovery Testing
- [ ] Test database restoration
- [ ] Test file restoration
- [ ] Document recovery procedures
- [ ] Train team on recovery process

---

### ✅ Documentation

#### User Documentation
- [ ] SYSTEM_DOCUMENTATION.md reviewed
- [ ] QUICK_START_GUIDE.md reviewed
- [ ] API documentation reviewed
- [ ] User training conducted

#### Technical Documentation
- [ ] Server configuration documented
- [ ] Database schema documented
- [ ] Deployment procedures documented
- [ ] Troubleshooting guide created
- [ ] Contact list for support

---

### ✅ Go-Live Preparation

#### Pre-Launch
- [ ] Final backup of development environment
- [ ] Code freeze 48 hours before launch
- [ ] Final testing completed
- [ ] Rollback plan documented
- [ ] Support team on standby

#### Launch Day
- [ ] Deploy application files
- [ ] Import production database
- [ ] Update configuration files
- [ ] Clear Redis cache
- [ ] Test critical workflows
- [ ] Monitor error logs
- [ ] Monitor performance

#### Post-Launch (First 24 Hours)
- [ ] Monitor error logs every hour
- [ ] Check user feedback
- [ ] Monitor server resources
- [ ] Verify backup completion
- [ ] Document any issues
- [ ] Quick fixes if needed

#### Post-Launch (First Week)
- [ ] Daily monitoring
- [ ] User feedback collection
- [ ] Performance optimization if needed
- [ ] Security audit
- [ ] Update documentation

---

### ✅ Maintenance Schedule

#### Daily
- [ ] Review error logs
- [ ] Monitor system performance
- [ ] Check backup completion
- [ ] Verify critical workflows

#### Weekly
- [ ] Review activity logs
- [ ] Analyze usage patterns
- [ ] Check for security updates
- [ ] Review user feedback

#### Monthly
- [ ] Database optimization (OPTIMIZE TABLE)
- [ ] Clear old logs (if not using rotation)
- [ ] Review and update documentation
- [ ] Security audit
- [ ] Performance review

#### Quarterly
- [ ] Full system audit
- [ ] Review and update permissions
- [ ] Review and optimize queries
- [ ] Review backup/recovery procedures
- [ ] Plan feature updates

---

## Troubleshooting Guide

### Common Issues

#### Login Issues
**Problem:** Cannot login
- Check Redis connection
- Verify user exists in database
- Verify user status is 'active'
- Check session configuration
- Clear browser cookies

#### Database Connection Issues
**Problem:** Database connection failed
- Verify MySQL is running
- Check database credentials
- Verify network connectivity
- Check firewall rules
- Review MySQL error log

#### Redis Connection Issues
**Problem:** Session errors
- Verify Redis is running
- Check Redis password
- Verify network connectivity
- Check Redis memory usage
- Review Redis logs

#### Permission Errors
**Problem:** User cannot access module
- Verify user roles assigned
- Check permission definitions
- Review role-permission mappings
- Clear Redis cache
- Check activity logs

#### Slow Performance
**Problem:** Pages loading slowly
- Check database query performance
- Review slow query log
- Optimize indexes
- Check server resources (CPU, RAM)
- Review Redis memory usage
- Enable query caching

---

## Rollback Procedures

### If Critical Issue Occurs

1. **Immediate Actions**
   - [ ] Notify stakeholders
   - [ ] Document the issue
   - [ ] Assess severity

2. **Rollback Steps**
   - [ ] Stop web server
   - [ ] Restore previous application files
   - [ ] Restore database backup
   - [ ] Clear Redis cache
   - [ ] Start web server
   - [ ] Test critical workflows

3. **Post-Rollback**
   - [ ] Communicate with users
   - [ ] Analyze root cause
   - [ ] Fix issue in development
   - [ ] Re-test thoroughly
   - [ ] Schedule new deployment

---

## Support Contacts

**System Administrator:** admin@barron
**Database Administrator:** [Email]
**Network Administrator:** [Email]
**Emergency Contact:** [Phone]
**Vendor Support:** [Contact Info]

---

## Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Project Manager | | | |
| Technical Lead | | | |
| Database Admin | | | |
| System Admin | | | |
| QA Lead | | | |
| Business Owner | | | |

---

**Deployment Date:** ______________
**Deployed By:** ______________
**Approved By:** ______________

---

**Version:** 1.1
**Last Updated:** January 8, 2026
**Barron (Pty) Ltd - Production Management System**
