# 🗄️ DATABASE IMPORT GUIDE

## Your Railway MySQL Database
```
Host: caboose.proxy.rlwy.net
Port: 20038
Database: railway
Username: root
Password: EDDEmqdRstvoHdqCmEflYJrnpaBwWajy
```

---

## ✅ OPTION 1: Railway CLI (RECOMMENDED - EASIEST)

### Step 1: Install Railway CLI
```powershell
npm install -g @railway/cli
```

### Step 2: Login to Railway
```powershell
railway login
```

### Step 3: Link Your Project
```powershell
railway link
```

### Step 4: Import Database
```powershell
railway run mysql -h caboose.proxy.rlwy.net -P 20038 -u root -pEDDEmqdRstvoHdqCmEflYJrnpaBwWajy railway < database/complete_schema.sql
```

**Time:** 2-3 minutes
**Result:** All tables created + admin user seeded

---

## ✅ OPTION 2: MySQL Workbench (GUI - EASY)

### Step 1: Download MySQL Workbench
https://dev.mysql.com/downloads/workbench/

### Step 2: Create Connection
- Hostname: `caboose.proxy.rlwy.net`
- Port: `20038`
- Username: `root`
- Password: `EDDEmqdRstvoHdqCmEflYJrnpaBwWajy`
- Default Schema: `railway`

### Step 3: Import Schema
1. Click **Server** → **Data Import**
2. Select **Import from Self-Contained File**
3. Browse to: `database/complete_schema.sql`
4. Select **railway** as Default Target Schema
5. Click **Start Import**

**Time:** 5 minutes
**Result:** Visual confirmation of all tables

---

## ✅ OPTION 3: Railway Web Dashboard

### Step 1: Access Railway Dashboard
1. Go to https://railway.app/
2. Open your **MySQL** service
3. Click **Data** tab

### Step 2: Open Query Console
1. Click **Query** button
2. Copy contents of `database/complete_schema.sql`
3. Paste into query window
4. Click **Run**

**Time:** 3-4 minutes
**Result:** Tables created directly in Railway

---

## ✅ OPTION 4: Install MySQL Command Line (Windows)

### Download MySQL Client Only
```powershell
# Using Chocolatey (if installed)
choco install mysql-cli

# OR download from:
# https://dev.mysql.com/downloads/mysql/
# Select "MySQL Installer for Windows"
# Choose "Custom" install
# Select only "MySQL Command Line Client"
```

### After Installation, Run:
```powershell
cd "C:\Users\4667.KevroAD\OneDrive - Barron (Pty) Ltd\Desktop\New folder"
mysql -h caboose.proxy.rlwy.net -P 20038 -u root -pEDDEmqdRstvoHdqCmEflYJrnpaBwWajy railway < database/complete_schema.sql
```

---

## 📋 What Gets Created

### 22+ Database Tables:
1. **Authentication:**
   - users
   - roles
   - permissions
   - user_roles
   - role_permissions

2. **Master Data:**
   - departments
   - department_stages
   - employees
   - employee_departments
   - machines
   - products

3. **Production Planning:**
   - production_orders
   - production_jobs
   - production_logs

4. **Quality Management:**
   - internal_rejects
   - customer_returns

5. **SOP Compliance:**
   - ncr_records
   - sop_tickets

6. **Maintenance:**
   - maintenance_schedules
   - maintenance_tickets

7. **Finance/BOM:**
   - bom_items

8. **System:**
   - audit_logs

### Seeded Data:
✅ **Admin User:**
- Email: `admin@barron`
- Password: `admin123` (bcrypt hashed)
- Role: Administrator
- All permissions granted

✅ **Default Roles:**
- Administrator
- Manager
- Supervisor
- Operator
- Viewer

✅ **17 Permissions:**
- manage_users, manage_roles, view_reports
- manage_departments, manage_employees, manage_machines, manage_products
- manage_orders, manage_production
- manage_quality, manage_ncr, manage_sop
- manage_maintenance
- manage_bom
- And more...

---

## 🔍 Verify Import Success

### After import, check table count:
```sql
SELECT COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = 'railway';
```
**Expected Result:** 22+ tables

### Check admin user exists:
```sql
SELECT id, username, email, first_name, last_name 
FROM users 
WHERE email = 'admin@barron';
```
**Expected Result:** 1 row with admin user

### Check roles:
```sql
SELECT COUNT(*) FROM roles;
```
**Expected Result:** 5 roles

---

## ⚡ FASTEST METHOD

**I recommend Option 1 (Railway CLI)** because:
- ✅ No additional software needed (just npm)
- ✅ Direct connection to Railway
- ✅ Handles large SQL files
- ✅ Simple one-command import
- ✅ Works perfectly with PowerShell

---

## 🆘 Troubleshooting

### Error: "Table already exists"
**Solution:** Tables won't be recreated (uses `IF NOT EXISTS`)

### Error: "Access denied"
**Solution:** Double-check password (no spaces)

### Error: "Can't connect to MySQL server"
**Solution:** Check Railway MySQL service is running

### Import takes long time
**Normal:** 591 lines of SQL takes 30-60 seconds

---

## ✅ After Successful Import

Your app will have:
- ✅ All database tables created
- ✅ Admin user ready: `admin@barron` / `admin123`
- ✅ Complete RBAC system configured
- ✅ Ready for production use!

**Next:** Access your Railway app URL and login! 🚀
