# QUICK START GUIDE
## Barron Production Management System

---

## 🚀 Getting Started in 5 Minutes

### Step 1: Install Database (2 minutes)

Open PowerShell in the project directory and run:

```powershell
.\install.ps1
```

This will:
- Connect to your Railway MySQL database
- Create all tables (30+ tables)
- Load initial data (roles, permissions, admin user)

**Password when prompted:** `hwemqHyJCOMkVycHiOcRqWBXnUryhFjw`

---

### Step 2: Configure Web Server (2 minutes)

#### Option A: Using XAMPP (Windows)
1. Copy project folder to `C:\xampp\htdocs\barron`
2. Start Apache from XAMPP Control Panel
3. Open browser: `http://localhost/barron/login.php`

#### Option B: Using PHP Built-in Server (Quick Test)
```powershell
php -S localhost:8000
```
Then open: `http://localhost:8000/login.php`

#### Option C: Using IIS (Windows Server)
1. Create new website pointing to project folder
2. Enable PHP CGI
3. Access via configured URL

---

### Step 3: First Login (1 minute)

1. Navigate to the login page
2. Enter credentials:
   - **Username:** `admin@barron`
   - **Password:** `admin123`
3. Click LOGIN

✅ You should now see the dashboard!

---

## 📱 Test on Mobile

1. Find your computer's IP address:
   ```powershell
   ipconfig
   ```
   Look for "IPv4 Address"

2. On your phone, open browser:
   ```
   http://[YOUR_IP]/barron/login.php
   ```
   Example: `http://192.168.1.100/barron/login.php`

---

## 🔧 Post-Installation Tasks

### 1. Change Admin Password (IMPORTANT!)
- Login as admin
- Go to profile settings
- Change password from `admin123` to something secure

### 2. Create First Department
- Navigate to Administration > Departments
- Click "Add Department"
- Fill in: Department Code, Name, Targets
- Save

### 3. Create First Employee
- Navigate to Administration > Employees
- Click "Add Employee"
- Fill in details:
  - Employee Number
  - First Name, Last Name
  - Department
  - Role
- Password will be auto-generated
- Username will be `firstname@barron`

---

## ✅ Verify Everything Works

### Test Checklist:

- [ ] Login successful
- [ ] Dashboard loads
- [ ] Sidebar navigation appears
- [ ] Stats cards show numbers (may be 0)
- [ ] Notification bell icon visible
- [ ] Can click Logout
- [ ] Mobile view is responsive

---

## 🆘 Troubleshooting

### Problem: "Database connection failed"
**Solution:** Check `config/database.php` credentials match Railway

### Problem: "Login failed" with correct password
**Solution:** 
1. Check database installed correctly
2. Verify admin user exists:
   ```sql
   SELECT * FROM employees WHERE username = 'admin@barron';
   ```

### Problem: Blank white page
**Solution:**
1. Check `logs/error.log`
2. Enable error display temporarily in `config/config.php`:
   ```php
   ini_set('display_errors', 1);
   ```

### Problem: CSS not loading
**Solution:** Check file paths in HTML and Apache/IIS URL rewriting

### Problem: Session timeout too fast
**Solution:** Adjust in `config/config.php`:
```php
define('SESSION_LIFETIME', 7200); // 2 hours instead of 1
```

---

## 📖 Next Steps

Once logged in, explore:

1. **Dashboard** - Overview and statistics
2. **Administration** - Master data management
3. **Planning** - Job scheduling (to be built)
4. **Quality** - Defects tracking (to be built)
5. **Maintenance** - Equipment management (to be built)

---

## 🎓 Key Concepts

### Username Format
All usernames follow the pattern: `firstname@barron`
- Example: John Smith → `john@barron`

### Passwords
- Operators: Employee Number
- Others: Set by admin or auto-generated

### Roles
15 different roles with specific permissions:
- Admin: Full access
- Manager: Department management
- Planner: Production planning
- Operator: Job execution
- etc.

### Navigation
- Sidebar shows only what you have permission to access
- Mobile: Hamburger menu
- Desktop: Always visible sidebar

---

## 💻 Development Notes

### File Structure:
- `api/` - All backend endpoints
- `assets/css/` - Stylesheets
- `assets/js/` - JavaScript files
- `modules/` - Feature modules (to be built)

### Making Changes:
1. CSS: Edit `assets/css/*.css`
2. JavaScript: Edit `assets/js/*.js`
3. PHP Logic: Edit in `classes/` or `api/`
4. Never mix inline styles/scripts

### Adding New Pages:
1. Create PHP file
2. Add `session_start()` at top
3. Include `config/config.php`
4. Call `requireLogin()`
5. Link stylesheets
6. Build content
7. Add to navigation menu

---

## 🔐 Security Reminders

✅ Change default admin password  
✅ Use strong passwords  
✅ Regularly backup database  
✅ Keep PHP updated  
✅ Monitor `audit_log` table  
✅ Review `logs/error.log` regularly  
✅ In production, enable HTTPS  

---

## 📞 Need Help?

- Check `README.md` for detailed documentation
- Check `PROJECT_STATUS.md` for what's completed
- Review database schema in `database/schema.sql`
- Check audit logs for debugging

---

**You're now ready to use the system!** 🎉

Start with creating departments and employees, then move on to building out the specific modules for your operations.
