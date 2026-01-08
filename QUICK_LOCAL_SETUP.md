# 🚀 QUICK START - Run App Locally NOW

**Run the Barron Production Management System on your local machine in 15 minutes!**

---

## ⚡ FASTEST WAY TO RUN THE APP

Since PHP is not installed, here are your options to run the app RIGHT NOW:

---

## 🎯 **OPTION 1: Install XAMPP (RECOMMENDED - 15 minutes)**

**This is the fastest way to get everything working!**

### Step 1: Download XAMPP (5 minutes)
1. Go to: **https://www.apachefriends.org/download.html**
2. Download: **XAMPP for Windows** (PHP 8.2 recommended)
3. File size: ~150 MB
4. While downloading, continue to Step 2 to prepare files

### Step 2: Prepare Application Files (Now!)
```powershell
# Your files are already here:
# C:\Users\4667.KevroAD\OneDrive - Barron (Pty) Ltd\Desktop\New folder
# 
# After XAMPP installs, we'll copy them to: C:\xampp\htdocs\barron\
```

### Step 3: Install XAMPP (5 minutes)
1. Run the downloaded installer
2. Install to: `C:\xampp` (default is fine)
3. Select components:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
4. Click through installation
5. Launch XAMPP Control Panel

### Step 4: Start Services (1 minute)
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both should show green "Running" status

### Step 5: Copy Application Files (2 minutes)

**Run these commands in PowerShell:**

```powershell
# Create barron directory in xampp
New-Item -ItemType Directory -Force -Path "C:\xampp\htdocs\barron"

# Copy all files from current directory to xampp
Copy-Item -Path "C:\Users\4667.KevroAD\OneDrive - Barron (Pty) Ltd\Desktop\New folder\*" -Destination "C:\xampp\htdocs\barron\" -Recurse -Force

Write-Host "Files copied successfully!" -ForegroundColor Green
```

### Step 6: Create Database (3 minutes)

1. Open browser: **http://localhost/phpmyadmin**
2. Click **"New"** in left sidebar
3. Database name: **`barron_production`**
4. Click **"Create"**
5. Click on **barron_production** database (in left sidebar)
6. Click **"Import"** tab at top
7. Click **"Choose File"**
8. Navigate to: `C:\xampp\htdocs\barron\database\complete_schema.sql`
9. Click **"Import"** button at bottom
10. Wait for success message (should see 22+ tables created)

### Step 7: Configure Application (2 minutes)

**Run these commands in PowerShell:**

```powershell
# Navigate to barron directory
cd "C:\xampp\htdocs\barron"

# Copy .env.example to .env
Copy-Item ".env.example" ".env"

# Open .env for editing
notepad .env
```

**In the .env file, update these lines:**

```ini
# Database Configuration (Local XAMPP)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=barron_production
DB_USERNAME=root
DB_PASSWORD=
# ☝️ Leave password EMPTY for XAMPP default

# Redis (Optional for local testing - can skip)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/barron
```

**Save and close the file**

### Step 8: Access Your App! 🎉

1. Open browser
2. Go to: **http://localhost/barron**
3. You should see the login page!

**Default Login:**
- Username: `admin@barron`
- Password: `admin123`

⚠️ **IMPORTANT:** Change this password immediately after logging in!

### Step 9: Complete the Tutorial (15 minutes)

Once logged in:
1. Open: **GETTING_STARTED.md** (in your project folder)
2. Follow the 15-minute interactive tutorial
3. Test all modules!

---

## 🎯 **OPTION 2: Portable PHP (ADVANCED - 30 minutes)**

If you don't want to install XAMPP, you can use portable PHP.

### Download Portable PHP
1. Go to: **https://windows.php.net/download/**
2. Download: **PHP 8.2 Thread Safe** (ZIP)
3. Extract to: `C:\php`

### Download MySQL Portable
This is complex. **XAMPP is much easier!**

**Recommendation: Use XAMPP (Option 1) - it's much simpler!**

---

## 🎯 **OPTION 3: Use Online PHP Testing (DEMO ONLY)**

For a **very quick demo** without installation:

1. Go to: **https://www.tutorialspoint.com/execute_php_online.php**
2. Copy a single PHP file from the project
3. Paste and run to see PHP code in action

**Note:** This is only for testing PHP code snippets, NOT for running the full app.

---

## 📋 **QUICK SETUP SUMMARY**

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│  1. Download XAMPP         (5 min)                     │
│  2. Install XAMPP          (5 min)                     │
│  3. Start Apache & MySQL   (1 min)                     │
│  4. Copy files to htdocs   (2 min)                     │
│  5. Import database        (3 min)                     │
│  6. Configure .env         (2 min)                     │
│  7. Access app             (1 min)                     │
│  8. Test & enjoy!          (15 min)                    │
│                                                         │
│  TOTAL TIME: 15-20 minutes                             │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🆘 **TROUBLESHOOTING**

### Issue: Port 80 already in use
**Solution:**
1. Check if Skype is running (it uses port 80)
2. Or change Apache port:
   - Edit `C:\xampp\apache\conf\httpd.conf`
   - Find: `Listen 80`
   - Change to: `Listen 8080`
   - Restart Apache
   - Access: `http://localhost:8080/barron`

### Issue: MySQL won't start
**Solution:**
1. Check if another MySQL is running
2. Stop other MySQL services
3. Or change MySQL port in XAMPP config

### Issue: Can't import database
**Solution:**
1. Check file size limits in phpMyAdmin
2. Use command line instead:
   ```powershell
   cd "C:\xampp\mysql\bin"
   .\mysql.exe -u root barron_production < "C:\xampp\htdocs\barron\database\complete_schema.sql"
   ```

### Issue: Blank page after accessing app
**Solution:**
1. Check `C:\xampp\htdocs\barron\logs\error.log`
2. Verify .env file exists
3. Check database connection in .env

---

## ✅ **AFTER SETUP CHECKLIST**

Once the app is running:

- [ ] Login successful (admin@barron / admin123)
- [ ] Dashboard loads
- [ ] Can navigate to Master Data → Departments
- [ ] Can navigate to Planning → Orders
- [ ] All menu items visible
- [ ] No errors in browser console (F12)

**If all checked, you're ready to follow GETTING_STARTED.md tutorial!**

---

## 🎓 **WHAT TO DO AFTER APP IS RUNNING**

1. **Change password immediately**
   - Click profile icon
   - Change from admin123 to something secure

2. **Complete tutorial**
   - Open: GETTING_STARTED.md
   - Follow 15-minute walkthrough
   - Create test data in all modules

3. **Explore the system**
   - Try creating a department
   - Add an employee
   - Register a machine
   - Create a product
   - Make a production order

4. **Test all features**
   - Schedule a job
   - Track production
   - Report a defect
   - Create maintenance ticket
   - Build a BOM

---

## 📞 **NEED HELP?**

If you get stuck:

1. **Check logs:**
   - Application: `C:\xampp\htdocs\barron\logs\error.log`
   - Apache: `C:\xampp\apache\logs\error.log`
   - PHP: `C:\xampp\php\logs\php_error.log`

2. **Check documentation:**
   - SYSTEM_MAP.md → Section 10 (Troubleshooting)
   - ADMIN_GUIDE.md → Troubleshooting section

3. **Verify setup:**
   - XAMPP Control Panel shows Apache & MySQL running (green)
   - Database exists: http://localhost/phpmyadmin
   - Files in: C:\xampp\htdocs\barron\

---

## 🚀 **LET'S GET STARTED!**

**Ready to install XAMPP and run the app?**

1. **Start download now:** https://www.apachefriends.org/download.html
2. **While downloading:** Read GETTING_STARTED.md to prepare
3. **After install:** Follow steps above
4. **Within 20 minutes:** You'll be using the app!

---

**Your system is ready. Let's make it run!** 🎉

---

**Document:** QUICK_LOCAL_SETUP.md  
**Version:** 1.0  
**Date:** January 8, 2026  
**Estimated Time:** 15-20 minutes total
