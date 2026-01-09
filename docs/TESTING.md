# Testing Guide - Barron Production Management System

## 🧪 Comprehensive Testing Strategy

This guide covers all testing procedures to ensure the system is production-ready.

---

## ✅ Pre-Testing Setup

### Test Environment
- **Browser:** Chrome, Firefox, Safari, Edge (latest versions)
- **Mobile:** iOS Safari, Android Chrome
- **Database:** Test database with seed data loaded
- **Users:** Test accounts for all roles (admin, manager, planner, operator)

### Test Data Preparation
```sql
-- Run seed scripts
mysql -u root -p test_database < sql/seed_master_data.sql

-- Verify test data loaded
SELECT COUNT(*) FROM products;
SELECT COUNT(*) FROM departments;
SELECT COUNT(*) FROM users;
```

---

## 🔐 Security Testing

### Authentication Tests

**Test Case 1.1: Login Success**
```
Steps:
1. Navigate to /pages/auth/login.php
2. Enter: username=admin, password=password
3. Click "Login"

Expected: Redirect to dashboard, session created
Status: [ ]
```

**Test Case 1.2: Login Failure - Invalid Credentials**
```
Steps:
1. Navigate to login page
2. Enter: username=admin, password=wrongpassword
3. Click "Login"

Expected: Error message "Invalid credentials", no session
Status: [ ]
```

**Test Case 1.3: Session Timeout**
```
Steps:
1. Login successfully
2. Wait 30 minutes (or adjust session timeout in config)
3. Try to access protected page

Expected: Redirect to login page
Status: [ ]
```

**Test Case 1.4: Logout**
```
Steps:
1. Login successfully
2. Click "Logout"

Expected: Session destroyed, redirect to login
Status: [ ]
```

### Authorization Tests (RBAC)

**Test Case 2.1: Admin Access**
```
Steps:
1. Login as admin
2. Access /pages/master/users.php

Expected: Page loads successfully, can create users
Status: [ ]
```

**Test Case 2.2: Operator Restricted Access**
```
Steps:
1. Login as operator
2. Try to access /pages/master/users.php

Expected: Redirect to dashboard or 403 error
Status: [ ]
```

**Test Case 2.3: API Permission Check**
```
Steps:
1. Login as operator
2. POST to /api/master/users.php?action=create

Expected: 403 Forbidden response
Status: [ ]
```

### SQL Injection Tests

**Test Case 3.1: Login SQL Injection**
```
Steps:
1. Navigate to login
2. Username: admin' OR '1'='1
3. Password: anything

Expected: Login fails, no SQL error exposed
Status: [ ]
```

**Test Case 3.2: Search Parameter Injection**
```
Steps:
1. Login as admin
2. Search jobs with: '; DROP TABLE jobs; --

Expected: Search returns no results, table not dropped
Status: [ ]
```

### XSS Tests

**Test Case 4.1: Stored XSS - Job Title**
```
Steps:
1. Create job with title: <script>alert('XSS')</script>
2. View job on dashboard

Expected: Title displayed as text, no alert shown
Status: [ ]
```

**Test Case 4.2: Reflected XSS - URL Parameter**
```
Steps:
1. Navigate to: /pages/jobs/details.php?id=<script>alert('XSS')</script>

Expected: Parameter escaped, no alert shown
Status: [ ]
```

### File Upload Security

**Test Case 5.1: Valid File Upload**
```
Steps:
1. Navigate to NCR details
2. Upload valid PDF file (< 10 MB)

Expected: File uploads successfully
Status: [ ]
```

**Test Case 5.2: Invalid File Type**
```
Steps:
1. Navigate to NCR details
2. Try to upload .exe file

Expected: Error message "Invalid file type"
Status: [ ]
```

**Test Case 5.3: Oversized File**
```
Steps:
1. Try to upload 15 MB file

Expected: Error message "File too large"
Status: [ ]
```

---

## 📋 Functional Testing

### Module 1: Job Planning

**Test Case 6.1: Create Job**
```
Steps:
1. Login as planner
2. Navigate to /pages/planning/create-job.php
3. Fill form: product_id=1, quantity=100, start_date=today
4. Click "Create Job"

Expected: Job created with auto-generated job_number (JOB-YYYYMMDD-####)
Verify: Check database for new record
Status: [ ]
```

**Test Case 6.2: View Job Details**
```
Steps:
1. Click on created job from dashboard
2. View job details page

Expected: All job info displayed correctly, stage progress shown
Status: [ ]
```

**Test Case 6.3: Update Job Status**
```
Steps:
1. Open job details
2. Click "Start Job"
3. Verify status changes to "in_progress"

Expected: Status updates in database, activity logged
Status: [ ]
```

**Test Case 6.4: Capacity Planning**
```
Steps:
1. Navigate to /pages/planning/capacity.php
2. View capacity chart

Expected: Chart displays with correct data, departments shown
Status: [ ]
```

### Module 2: Defects Management

**Test Case 7.1: Report Defect**
```
Steps:
1. Login as operator
2. Navigate to /pages/defects/create.php
3. Fill form: job_id, severity=high, description
4. Submit

Expected: Defect created with DEF-YYYYMMDD-#### number
Status: [ ]
```

**Test Case 7.2: Create Replacement Ticket**
```
Steps:
1. Open defect details
2. Click "Create Replacement Ticket"
3. Fill quantity and reason
4. Submit

Expected: Replacement ticket created with RPL-YYYYMMDD-#### number
Status: [ ]
```

**Test Case 7.3: Approve Replacement**
```
Steps:
1. Login as manager
2. Navigate to replacement ticket
3. Click "Approve"

Expected: Status changes to "approved", notification sent
Status: [ ]
```

**Test Case 7.4: QC Report Analytics**
```
Steps:
1. Navigate to /pages/defects/qc-reports.php
2. View charts

Expected: Pie chart and bar chart display with data
Status: [ ]
```

### Module 3: NCR / SOP

**Test Case 8.1: Create NCR**
```
Steps:
1. Login as admin
2. Navigate to /pages/ncr/create.php
3. Fill form: title, description, department, severity
4. Submit

Expected: NCR created with NCR-YYYYMMDD-#### number
Status: [ ]
```

**Test Case 8.2: Upload SOP Attachment**
```
Steps:
1. Open NCR details
2. Upload PDF file
3. Verify file appears in attachments list

Expected: File saved to uploads/ncr_attachments/, record in database
Status: [ ]
```

**Test Case 8.3: Manager Review**
```
Steps:
1. Login as manager
2. Open NCR details
3. Add review notes and select "Approve"
4. Submit

Expected: Status changes to "approved", reviewed_by set
Status: [ ]
```

### Module 4: Maintenance

**Test Case 9.1: Create Machine**
```
Steps:
1. Navigate to /api/maintenance/machines.php
2. POST: {name: "CNC Mill", code: "CNC-001", department_id: 1}

Expected: Machine created in database
Status: [ ]
```

**Test Case 9.2: Schedule Maintenance Task**
```
Steps:
1. Navigate to /pages/maintenance/create.php
2. Fill form: machine, type=preventive, scheduled_date=tomorrow
3. Assign to user
4. Submit

Expected: Task created with MNT-YYYYMMDD-#### number
Status: [ ]
```

**Test Case 9.3: Log Activity**
```
Steps:
1. Open task details
2. Add activity log with hours_spent=2.5
3. Submit

Expected: Activity logged, hours tracked
Status: [ ]
```

**Test Case 9.4: Calendar View**
```
Steps:
1. Navigate to /pages/maintenance/calendar.php
2. View current month

Expected: Calendar displays with color-coded tasks
Status: [ ]
```

**Test Case 9.5: Overdue Detection**
```
Steps:
1. Create task with scheduled_date in past
2. View dashboard

Expected: Overdue count shows in red alert badge
Status: [ ]
```

### Module 5: Finance & BOM

**Test Case 10.1: Create BOM**
```
Steps:
1. Navigate to /pages/finance/bom-editor.php
2. Select product
3. Create new BOM version 1.0

Expected: BOM created with draft status
Status: [ ]
```

**Test Case 10.2: Add BOM Items**
```
Steps:
1. Open BOM details
2. Add material: quantity=5, unit_cost=10.00
3. Submit

Expected: Item added, total cost = $50.00
Status: [ ]
```

**Test Case 10.3: Activate BOM**
```
Steps:
1. Click "Activate" button
2. Confirm

Expected: BOM status changes to "active"
Status: [ ]
```

**Test Case 10.4: Calculate Job Cost**
```
Steps:
1. Navigate to /pages/finance/reports.php
2. Enter job_id
3. Click "Calculate"

Expected: Cost breakdown displayed (materials/labor/overhead)
Status: [ ]
```

**Test Case 10.5: Material Requirements Planning**
```
Steps:
1. Navigate to reports page
2. Enter product_id and quantity=100
3. Click "Calculate Requirements"

Expected: Table shows all materials needed with totals
Status: [ ]
```

**Test Case 10.6: Financial Summary**
```
Steps:
1. Select date range
2. Click "Load Summary"

Expected: Summary cards display, pie chart renders
Status: [ ]
```

### Module 6: Notifications

**Test Case 11.1: Create Notification**
```
Steps:
1. Trigger defect creation (which should send notification)
2. Check notification badge

Expected: Badge count increases
Status: [ ]
```

**Test Case 11.2: Mark as Read**
```
Steps:
1. Click notification badge
2. Click notification
3. Click "Mark as Read"

Expected: Notification marked read, badge count decreases
Status: [ ]
```

**Test Case 11.3: Notification Queue Processing**
```
Steps:
1. Run: php scripts/process_notification_queue.php
2. Check logs/email_log.txt

Expected: Email logged, queue processed
Status: [ ]
```

### Module 7: Master Data

**Test Case 12.1: Create Product**
```
Steps:
1. Login as admin
2. Navigate to /pages/master/products.php
3. Add new product with name, SKU, unit
4. Submit

Expected: Product created, appears in list
Status: [ ]
```

**Test Case 12.2: Create Department**
```
Steps:
1. Navigate to /pages/master/departments.php
2. Add department with name and code
3. Submit

Expected: Department created
Status: [ ]
```

**Test Case 12.3: Create User**
```
Steps:
1. Navigate to /pages/master/users.php
2. Add user with username, password, role
3. Submit

Expected: User created with hashed password
Status: [ ]
```

---

## 📱 Mobile Testing (Operator Interface)

**Test Case 13.1: Mobile Responsive**
```
Device: iPhone/Android
Steps:
1. Navigate to /pages/operator/dashboard.php
2. Check layout

Expected: Touch-friendly buttons, readable text, no horizontal scroll
Status: [ ]
```

**Test Case 13.2: Job Progress Update**
```
Device: Mobile
Steps:
1. Login as operator
2. Scan or select job
3. Update progress

Expected: Progress updates successfully
Status: [ ]
```

**Test Case 13.3: Quick Defect Report**
```
Device: Mobile
Steps:
1. Access /pages/operator/defect-report.php
2. Fill form and submit

Expected: Form submits, defect created
Status: [ ]
```

---

## ⚡ Performance Testing

### Load Time Tests

**Test Case 14.1: Dashboard Load Time**
```
Steps:
1. Clear browser cache
2. Login and navigate to dashboard
3. Measure load time with DevTools

Expected: < 2 seconds
Actual: _____ seconds
Status: [ ]
```

**Test Case 14.2: Reports with Charts Load Time**
```
Steps:
1. Navigate to /pages/finance/reports.php
2. Load financial summary
3. Measure time to chart render

Expected: < 3 seconds
Actual: _____ seconds
Status: [ ]
```

### Database Performance

**Test Case 14.3: Query Performance**
```
Steps:
1. Enable MySQL slow query log
2. Use system for 10 minutes
3. Check slow query log

Expected: No queries > 1 second
Status: [ ]
```

**Test Case 14.4: Index Usage**
```
SQL:
EXPLAIN SELECT * FROM jobs WHERE status = 'pending';
EXPLAIN SELECT * FROM defects WHERE job_id = 1;

Expected: 'key' column shows index used
Status: [ ]
```

---

## 🌐 Cross-Browser Testing

**Test Case 15: Browser Compatibility**

| Browser | Version | Dashboard | Forms | Charts | Mobile | Status |
|---------|---------|-----------|-------|--------|--------|--------|
| Chrome  | Latest  | [ ]       | [ ]   | [ ]    | [ ]    | [ ]    |
| Firefox | Latest  | [ ]       | [ ]   | [ ]    | [ ]    | [ ]    |
| Safari  | Latest  | [ ]       | [ ]   | [ ]    | [ ]    | [ ]    |
| Edge    | Latest  | [ ]       | [ ]   | [ ]    | [ ]    | [ ]    |

---

## 🔄 Integration Testing

### API Integration

**Test Case 16.1: Job Creation Flow**
```
Steps:
1. POST /api/jobs/jobs.php?action=create
2. GET /api/jobs/jobs.php?action=detail
3. POST /api/jobs/jobs.php?action=update

Expected: All operations succeed, data consistent
Status: [ ]
```

**Test Case 16.2: Defect to Replacement Flow**
```
Steps:
1. Create defect via API
2. Create replacement ticket via API
3. Approve replacement
4. Verify notification sent

Expected: Complete workflow executes, all records created
Status: [ ]
```

### Module Integration

**Test Case 17.1: Job Cost Calculation Integration**
```
Steps:
1. Create BOM with materials
2. Create job for that product
3. Log maintenance hours for job
4. Calculate job cost

Expected: Cost calculation includes BOM materials and labor hours
Status: [ ]
```

---

## 📊 Data Integrity Testing

**Test Case 18.1: Foreign Key Constraints**
```
Steps:
1. Try to delete product used in BOM
2. Try to delete user assigned to task

Expected: Database prevents deletion or cascades appropriately
Status: [ ]
```

**Test Case 18.2: Auto-Numbering Uniqueness**
```
Steps:
1. Create 10 jobs simultaneously
2. Check all have unique job_numbers

Expected: No duplicate numbers, sequential increment
Status: [ ]
```

**Test Case 18.3: Transaction Rollback**
```
Steps:
1. Start job status update (which triggers multiple table updates)
2. Simulate error mid-transaction
3. Check database state

Expected: All changes rolled back, no partial updates
Status: [ ]
```

---

## 🛡️ Error Handling Testing

**Test Case 19.1: Database Connection Failure**
```
Steps:
1. Stop MySQL service
2. Try to login

Expected: Graceful error message, no stack trace exposed
Status: [ ]
```

**Test Case 19.2: Missing Required Field**
```
Steps:
1. Submit job creation form without required fields

Expected: Validation error message, form not submitted
Status: [ ]
```

**Test Case 19.3: Invalid JSON in API**
```
Steps:
1. POST invalid JSON to API endpoint

Expected: 400 Bad Request with error message
Status: [ ]
```

---

## 📝 Test Results Summary

### Overall Results
- **Total Tests:** ___
- **Passed:** ___
- **Failed:** ___
- **Pass Rate:** ___%

### Critical Issues Found
1. 
2. 
3. 

### Non-Critical Issues
1. 
2. 
3. 

### Recommendations
1. 
2. 
3. 

---

## 🚀 Production Readiness Checklist

- [ ] All critical tests passed
- [ ] Security tests passed
- [ ] Performance acceptable (< 2s load times)
- [ ] Cross-browser compatibility verified
- [ ] Mobile responsiveness confirmed
- [ ] Error handling tested
- [ ] Database integrity verified
- [ ] Backup/restore tested
- [ ] Documentation complete
- [ ] User training completed

---

## 📞 Bug Reporting Template

```markdown
**Bug Title:** Brief description

**Severity:** Critical / High / Medium / Low

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Behavior:**

**Actual Behavior:**

**Environment:**
- Browser: 
- OS: 
- User Role: 

**Screenshots:**

**Error Messages:**
```

---

**Last Updated:** January 9, 2026  
**Version:** 1.0.0
