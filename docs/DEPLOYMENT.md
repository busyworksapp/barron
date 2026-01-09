# Deployment Guide - Barron Production Management System

## 🚀 Production Deployment Checklist

### Pre-Deployment Checks

#### ✅ Code Quality
- [ ] All files committed to Git
- [ ] No debug code or console.log statements
- [ ] Error handling implemented on all APIs
- [ ] SQL queries use prepared statements
- [ ] File permissions set correctly

#### ✅ Database
- [ ] All schema files executed
- [ ] Indexes created on frequently queried columns
- [ ] Foreign keys properly configured
- [ ] Seed data loaded (master data)
- [ ] Database backups configured

#### ✅ Security
- [ ] Default passwords changed
- [ ] .env file not in Git repository
- [ ] Session security configured
- [ ] File upload validation active
- [ ] HTTPS enabled on production
- [ ] RBAC enforced on all endpoints

#### ✅ Performance
- [ ] Database queries optimized
- [ ] Images optimized for web
- [ ] CDN links working (Chart.js, Bootstrap)
- [ ] File upload size limits set
- [ ] Log rotation configured

#### ✅ Monitoring
- [ ] Error logging enabled
- [ ] Performance monitoring ready
- [ ] Backup schedule confirmed
- [ ] Uptime monitoring configured

---

## 🌐 Deployment Options

### Option 1: Railway (Current Configuration)

**Advantages:**
- Auto-deploy on Git push
- Managed MySQL database
- SSL certificates automatic
- Easy rollback

**Current Setup:**
```
Database: caboose.proxy.rlwy.net:20038
Auto-deploy: Enabled on main branch
Environment: Production
```

**Deploy Process:**
```bash
# 1. Commit changes
git add .
git commit -m "Your changes"

# 2. Push to main branch
git push origin main

# 3. Railway automatically deploys
# Check status at: https://railway.app
```

**Railway Environment Variables:**
```
DB_HOST=caboose.proxy.rlwy.net
DB_PORT=20038
DB_NAME=railway
DB_USER=root
DB_PASS=<your-railway-password>
APP_ENV=production
APP_URL=https://your-app.railway.app
```

---

### Option 2: Traditional Web Hosting

**Requirements:**
- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx
- SSL certificate
- SSH access

#### Step 1: Server Preparation

**Install Dependencies (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl
sudo apt install mysql-server
sudo apt install apache2
```

**Enable Required Modules:**
```bash
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

#### Step 2: Upload Files

**Via Git:**
```bash
cd /var/www
sudo git clone https://github.com/busyworksapp/barron.git
cd barron
sudo chown -R www-data:www-data .
```

**Via FTP/SFTP:**
```bash
# Upload all files to your web directory
# Exclude: .git, .env.example, node_modules (if any)
```

#### Step 3: Configure Environment

**Create .env file:**
```bash
cd /var/www/barron
sudo cp .env.example .env
sudo nano .env
```

**Edit .env:**
```env
# Database
DB_HOST=localhost
DB_NAME=barron_production
DB_USER=barron_user
DB_PASS=secure_password_here

# Application
APP_ENV=production
APP_URL=https://barron.yourcompany.com
APP_DEBUG=false

# Email (optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=notifications@yourcompany.com
SMTP_PASS=your_app_password

# SMS (optional)
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890
```

#### Step 4: Database Setup

**Create Database:**
```bash
mysql -u root -p
```

```sql
CREATE DATABASE barron_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'barron_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON barron_production.* TO 'barron_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Import Schema:**
```bash
cd /var/www/barron
mysql -u barron_user -p barron_production < sql/schema.sql
mysql -u barron_user -p barron_production < sql/seed_master_data.sql
mysql -u barron_user -p barron_production < sql/ncr_schema.sql
mysql -u barron_user -p barron_production < sql/maintenance_schema.sql
mysql -u barron_user -p barron_production < sql/notifications_schema.sql
mysql -u barron_user -p barron_production < sql/finance_schema.sql
```

#### Step 5: Set Permissions

```bash
# Set directory permissions
sudo chmod -R 755 /var/www/barron
sudo chmod -R 775 /var/www/barron/uploads
sudo chmod -R 775 /var/www/barron/logs

# Set ownership
sudo chown -R www-data:www-data /var/www/barron

# Secure sensitive files
sudo chmod 600 /var/www/barron/.env
```

#### Step 6: Apache Configuration

**Create Virtual Host:**
```bash
sudo nano /etc/apache2/sites-available/barron.conf
```

**Configuration:**
```apache
<VirtualHost *:80>
    ServerName barron.yourcompany.com
    ServerAlias www.barron.yourcompany.com
    
    DocumentRoot /var/www/barron
    
    <Directory /var/www/barron>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog ${APACHE_LOG_DIR}/barron-error.log
    CustomLog ${APACHE_LOG_DIR}/barron-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName barron.yourcompany.com
    ServerAlias www.barron.yourcompany.com
    
    DocumentRoot /var/www/barron
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/barron.crt
    SSLCertificateKeyFile /etc/ssl/private/barron.key
    
    <Directory /var/www/barron>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog ${APACHE_LOG_DIR}/barron-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/barron-ssl-access.log combined
</VirtualHost>
```

**Enable Site:**
```bash
sudo a2ensite barron
sudo systemctl reload apache2
```

#### Step 7: SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d barron.yourcompany.com -d www.barron.yourcompany.com
```

#### Step 8: Cron Jobs

**Set up background tasks:**
```bash
sudo crontab -e
```

**Add tasks:**
```cron
# Process notification queue every 5 minutes
*/5 * * * * php /var/www/barron/scripts/process_notification_queue.php >> /var/www/barron/logs/cron.log 2>&1

# Check overdue maintenance daily at 2 AM
0 2 * * * php /var/www/barron/scripts/check_overdue_maintenance.php >> /var/www/barron/logs/cron.log 2>&1

# Daily database backup at 3 AM
0 3 * * * mysqldump -u barron_user -p'your_password' barron_production > /var/backups/barron_$(date +\%Y\%m\%d).sql

# Clean old logs weekly
0 0 * * 0 find /var/www/barron/logs -name "*.log" -mtime +30 -delete
```

---

### Option 3: Docker Deployment

**Dockerfile:**
```dockerfile
FROM php:8.1-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 775 /var/www/html/uploads
RUN chmod -R 775 /var/www/html/logs

EXPOSE 80
```

**docker-compose.yml:**
```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./uploads:/var/www/html/uploads
      - ./logs:/var/www/html/logs
    environment:
      - DB_HOST=db
      - DB_NAME=barron
      - DB_USER=barron
      - DB_PASS=secure_password
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=barron
      - MYSQL_USER=barron
      - MYSQL_PASSWORD=secure_password
    volumes:
      - mysql-data:/var/lib/mysql
      - ./sql:/docker-entrypoint-initdb.d

volumes:
  mysql-data:
```

**Deploy:**
```bash
docker-compose up -d
```

---

## 🔧 Post-Deployment

### 1. Verify Installation

**Check database connection:**
```bash
php -r "require 'includes/config.php'; echo 'Connection OK';"
```

**Check file permissions:**
```bash
ls -la uploads/
ls -la logs/
```

### 2. Test Core Functions

- [ ] Login with default admin account
- [ ] Change admin password
- [ ] Create test job
- [ ] Upload test file (NCR attachment)
- [ ] Send test notification
- [ ] View reports page

### 3. Configure Admin Settings

**Login as admin and:**
- [ ] Change default passwords for all seed users
- [ ] Add real departments
- [ ] Add real products
- [ ] Configure production stages
- [ ] Add actual users with correct roles

### 4. Backup Strategy

**Automated Backups:**
```bash
# Database backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/barron"
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u barron_user -p'password' barron_production | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploaded files
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/barron/uploads/

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

### 5. Monitoring Setup

**Log Monitoring:**
```bash
# Watch error logs
tail -f /var/www/barron/logs/error.log

# Watch access patterns
tail -f /var/log/apache2/barron-access.log
```

**Performance Monitoring:**
- Set up Uptime Robot or Pingdom
- Configure New Relic or similar APM
- Enable slow query logging in MySQL

---

## 🔐 Security Hardening

### 1. Environment Security

**Disable directory listing:**
```apache
Options -Indexes
```

**Hide PHP version:**
```ini
# In php.ini
expose_php = Off
```

### 2. Database Security

**Restrict database access:**
```sql
-- Only allow from application server
GRANT ALL PRIVILEGES ON barron_production.* TO 'barron_user'@'app-server-ip';
FLUSH PRIVILEGES;
```

**Enable SSL for database:**
```ini
# In MySQL config
require_secure_transport=ON
```

### 3. Application Security

**Update includes/config.php:**
```php
// Force HTTPS in production
if ($_SERVER['APP_ENV'] === 'production' && empty($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

---

## 🚨 Troubleshooting

### Common Issues

**"Database connection failed"**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -h localhost -u barron_user -p

# Check .env file
cat .env | grep DB_
```

**"Permission denied" on uploads**
```bash
# Fix permissions
sudo chown -R www-data:www-data uploads/
sudo chmod -R 775 uploads/
```

**"Page not found" errors**
```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess exists
ls -la .htaccess
```

**"Session errors"**
```bash
# Check session directory
ls -la /var/lib/php/sessions

# Fix permissions
sudo chown -R www-data:www-data /var/lib/php/sessions
```

---

## 📞 Support

**Deployment Issues:** support@barron.com  
**Emergency:** +27 (0) XX XXX XXXX  
**Documentation:** https://github.com/busyworksapp/barron

---

**Last Updated:** January 9, 2026  
**Version:** 1.0.0
