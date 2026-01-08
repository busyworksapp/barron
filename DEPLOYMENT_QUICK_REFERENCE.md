# 🚀 BARRON SYSTEM - QUICK DEPLOYMENT REFERENCE

**Updated:** January 8, 2026
**Repository:** https://github.com/busyworksapp/barron.git

---

## 📊 YOUR RAILWAY CREDENTIALS

### MySQL Database
```
Host: caboose.proxy.rlwy.net
Port: 20038
Database: railway
Username: root
Password: EDDEmqdRstvoHdqCmEflYJrnpaBwWajy

Connection String:
mysql://root:EDDEmqdRstvoHdqCmEflYJrnpaBwWajy@caboose.proxy.rlwy.net:20038/railway

CLI Command:
mysql -h caboose.proxy.rlwy.net -u root -pEDDEmqdRstvoHdqCmEflYJrnpaBwWajy --port 20038 --protocol=TCP railway
```

### Redis Cache
```
Host: shortline.proxy.rlwy.net
Port: 52214
Password: XtgNxbfliemuheWayTxbbHYedMFtdFjz

Connection String:
redis://default:XtgNxbfliemuheWayTxbbHYedMFtdFjz@shortline.proxy.rlwy.net:52214

CLI Command:
redis-cli -u redis://default:XtgNxbfliemuheWayTxbbHYedMFtdFjz@shortline.proxy.rlwy.net:52214
```

---

## 🚂 DEPLOY TO RAILWAY (10 MINUTES)

### Step 1: Login to Railway
Go to: **https://railway.app/**
- Login with **GitHub** (busyworksapp account)

### Step 2: Create New Project
- Click **"New Project"**
- Select **"Deploy from GitHub repo"**
- Choose: **busyworksapp/barron**
- Click **"Deploy Now"**

### Step 3: Add Environment Variables
In Railway dashboard → Your project → **Variables** tab:

```
DB_HOST=caboose.proxy.rlwy.net
DB_PORT=20038
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=EDDEmqdRstvoHdqCmEflYJrnpaBwWajy
REDIS_HOST=shortline.proxy.rlwy.net
REDIS_PORT=52214
REDIS_PASSWORD=XtgNxbfliemuheWayTxbbHYedMFtdFjz
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=redis
SESSION_LIFETIME=1440
```

### Step 4: Import Database Schema
Using terminal (one-time setup):
```bash
mysql -h caboose.proxy.rlwy.net -P 20038 -u root -pEDDEmqdRstvoHdqCmEflYJrnpaBwWajy railway < database/complete_schema.sql
```

### Step 5: Access Your App
Railway will give you a URL like:
**https://barron-production-xxxx.up.railway.app**

Open in browser → Login with:
- Email: `admin@barron`
- Password: `admin123`

✅ **DONE! Your app is LIVE!**

---

## 🖥️ LOCAL XAMPP SETUP (ALTERNATIVE)

### Quick Setup (15 minutes)
1. **Download XAMPP:** https://www.apachefriends.org/
2. **Install** to `C:\xampp`
3. **Copy app** to `C:\xampp\htdocs\barron\`
4. **Create .env** file (copy from .env.example with credentials above)
5. **Import database** using command above
6. **Start Apache** in XAMPP Control Panel
7. **Open:** http://localhost/barron
8. **Login:** admin@barron / admin123

---

## 📂 WHAT'S INCLUDED

✅ **7 Complete Modules:**
- Master Data (Departments, Employees, Machines, Products)
- Production Planning (Orders, Jobs, Schedule, Tracking)
- Quality Management (Internal Rejects, Customer Returns)
- SOP Compliance (NCRs, Tickets)
- Maintenance (Schedule, Tickets)
- Finance/BOM (Bill of Materials)
- Authentication & RBAC (17 permissions)

✅ **74 RESTful APIs**
✅ **16 Professional User Interfaces**
✅ **22+ Database Tables** (normalized schema)
✅ **Enterprise Security** (bcrypt, Redis sessions, audit logs)
✅ **21+ Documentation Files**

---

## 🔐 DEFAULT LOGIN

**Admin Account:**
- Email: `admin@barron`
- Password: `admin123`

⚠️ **IMPORTANT:** Change password immediately after first login!

---

## 📖 DETAILED GUIDES

- **Full Deployment:** See `RAILWAY_DEPLOYMENT_GUIDE.md`
- **Local Setup:** See `INSTALL_AND_RUN.md`
- **Getting Started:** See `GETTING_STARTED.md`
- **System Overview:** See `README.md`
- **All Docs:** See `DOCUMENTATION_INDEX.md`

---

## 🆘 NEED HELP?

### Troubleshooting
1. Check Railway logs for errors
2. Verify environment variables are set correctly
3. Ensure database schema is imported
4. Check Redis connection

### Documentation
- Master Index: `MASTER_INDEX.md`
- Admin Guide: `ADMIN_GUIDE.md`
- System Map: `SYSTEM_MAP.md`

---

## 🎯 NEXT STEPS AFTER DEPLOYMENT

1. ✅ Login with admin credentials
2. ✅ Change default password
3. ✅ Create departments and employees
4. ✅ Add machines and products
5. ✅ Create first production order
6. ✅ Follow `GETTING_STARTED.md` tutorial (15 minutes)

---

## 📊 SYSTEM STATS

- **Total Code:** 19,600+ lines
- **Documentation:** 9,000+ lines
- **Total Files:** 168+
- **Database Tables:** 22+
- **API Endpoints:** 74
- **User Pages:** 16
- **Modules:** 7

---

**Status:** ✅ Production Ready
**Version:** 1.1
**Last Updated:** January 8, 2026
**Repository:** https://github.com/busyworksapp/barron.git

---

🚀 **Ready to deploy? Go to https://railway.app/ and follow the steps above!**
