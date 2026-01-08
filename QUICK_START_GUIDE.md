# Barron Production Management System - Quick Start Guide

## 🚀 Getting Started

### Default Login
- **URL:** http://yourdomain.com/
- **Username:** `admin@barron`
- **Password:** `admin123`

---

## 📋 Module Quick Reference

### 1️⃣ MASTER DATA (Administration)
**Navigate:** Sidebar → ADMINISTRATION

#### Departments
- Manage production departments
- Define production stages
- Set department capacity
- Assign department heads

#### Employees
- Add/edit employee profiles
- Assign to departments (multiple)
- Assign user roles for system access
- Track hire/termination dates
- Username auto-generated: `firstname@barron`

#### Machines
- Register equipment
- Track maintenance schedules
- Monitor machine status (Available/In Use/Maintenance/Down)
- Store specifications

#### Products
- Product catalog with SKU codes
- Category management
- Specifications (JSON format)
- Search functionality
- Active/inactive status

---

### 2️⃣ JOB PLANNING
**Navigate:** Sidebar → PLANNING

#### Orders
- Create customer orders
- Add multiple items per order
- Track order status: Pending → Confirmed → In Production → Completed
- Set due dates and priorities
- View customer information

#### Job Scheduling
- Convert orders to production jobs
- Assign machines and operators
- Plan start/end dates
- Track setup time
- Monitor job status

#### Production Tracking
- Log production quantities
- Record scrap/waste
- Add operator notes
- Track progress percentage
- Real-time status updates

---

### 3️⃣ QUALITY CONTROL
**Navigate:** Sidebar → QUALITY

#### Internal Rejects
- Log production defects
- 9 defect types available
- Link to production jobs
- Approval workflow
- Disposition: Scrap/Rework/Use As-Is/Return to Supplier
- View reject rate %

#### Customer Returns
- Create RMA (Return Material Authorization)
- Link to original order
- 8 return reasons
- Investigation workflow
- 5 resolution types (Refund/Replacement/Credit/Repair/No Action)
- Track refund amounts and restocking fees
- View return rate %

---

### 4️⃣ COMPLIANCE
**Navigate:** Sidebar → COMPLIANCE

#### SOP Failure Tickets
- Document SOP violations
- 4 severity levels (Low/Medium/High/Critical)
- Root cause analysis
- Corrective action planning
- Assign to employees
- Track target closure dates

#### NCR Reports
- Non-Conformance Report system
- 3 types: Internal/Supplier/Customer
- Full CAPA workflow (Corrective & Preventive Actions)
- Root cause documentation
- Effectiveness verification
- Overdue alerts

---

### 5️⃣ MAINTENANCE
**Navigate:** Sidebar → MAINTENANCE

#### Maintenance Tickets
- Create work orders
- 4 types: Breakdown/Preventive/Inspection/Calibration
- 4 priority levels
- Assign technicians
- Track downtime hours
- Record costs and parts used
- Auto-update machine status

#### Preventive Maintenance Schedule
- Define recurring maintenance tasks
- 6 frequencies (Daily to Annual)
- Automatic next due date calculation
- Visual overdue alerts
- Task checklists
- One-click "Mark as Performed"
- Due soon warnings (7 days)

---

### 6️⃣ FINANCE
**Navigate:** Sidebar → FINANCE

#### Bill of Materials (BOM)
- Create product cost structures
- Add multiple components per BOM
- Automatic cost calculation
- 3 status states (Draft/Active/Obsolete)
- Version control for BOMs
- Overhead percentage calculation
- 6 unit types (pieces/kg/meters/liters/boxes/sets)
- Material cost breakdown
- Cost analysis and reporting

---

## 🎯 Common Workflows

### Create a New Production Order
1. Go to **PLANNING** → **Orders**
2. Click **Create Order**
3. Enter customer details
4. Add order items (product + quantity)
5. Set due date and priority
6. Save order
7. Order status: Pending → Confirm when ready

### Schedule a Production Job
1. Go to **PLANNING** → **Job Scheduling**
2. Click **Create Job**
3. Select order and product
4. Assign machine and operator
5. Set planned quantity and dates
6. Save job
7. Job status: Scheduled → Start production

### Log Production Progress
1. Go to **PRODUCTION** → **Production Tracking**
2. Find the active job
3. Click **Log Production**
4. Enter quantity produced and scrap
5. Add operator notes
6. Save log entry
7. Progress auto-calculated

### Handle a Production Defect
1. Go to **QUALITY** → **Internal Rejects**
2. Click **Log Reject**
3. Select job and product
4. Choose defect type
5. Enter quantity rejected (≤ produced)
6. Select disposition
7. Submit for approval
8. Manager approves/rejects

### Process a Customer Return
1. Go to **QUALITY** → **Customer Returns**
2. Click **Create Return**
3. Generate RMA number (auto)
4. Select order and product
5. Choose return reason
6. Document customer complaint
7. Add investigation notes
8. Select resolution type
9. Mark as resolved when done

### Create Maintenance Ticket
1. Go to **MAINTENANCE** → **Maintenance Tickets**
2. Click **Create Ticket**
3. Select machine
4. Choose type (Breakdown/Preventive/etc.)
5. Set priority
6. Describe issue
7. Assign technician
8. Track until completion

### Set Up Preventive Maintenance
1. Go to **MAINTENANCE** → **Preventive Maintenance Schedule**
2. Click **Add Schedule**
3. Name the task
4. Select machine
5. Describe maintenance steps
6. Choose frequency
7. Set next due date
8. Save schedule
9. System auto-schedules future dates

### Create Product BOM
1. Go to **FINANCE** → **Bill of Materials**
2. Click **Create BOM**
3. Select product
4. Enter version number
5. Add components:
   - Component name/part number
   - Quantity and unit
   - Unit cost
6. Set overhead percentage
7. System calculates total cost automatically
8. Save BOM (Draft or Active)
9. View cost breakdown

---

## 📊 Dashboard Metrics

Each module shows real-time statistics:

- **Orders:** Total, in production, completed, overdue
- **Jobs:** Scheduled, active, completed this week
- **Rejects:** Pending approval, approved, reject rate %
- **Returns:** Open, resolved, return rate %
- **SOP Tickets:** Open, resolved, critical severity
- **NCR:** Open, closed, overdue CAPA
- **Maintenance:** Open tickets, in-progress, urgent priority
- **PM Schedule:** Active schedules, overdue, due this week
- **BOM:** Active BOMs, approved, draft, average BOM cost

---

## 🔐 User Management

### Adding a New Employee
1. **ADMINISTRATION** → **Employees** → **Add Employee**
2. Enter personal details
3. Username auto-generated: `firstname@barron`
4. Set initial password
5. Assign to department(s)
6. Assign roles for system access
7. Save employee

### Assigning System Access
Roles available:
- Master Data (View/Edit)
- Planning (View/Edit)
- Production (View/Edit)
- Defects (View/Edit/Approve)
- SOP/Compliance (View/Edit)
- Maintenance (View/Edit)
- Finance (View BOM/Edit BOM)
- Operator (View Jobs)

---

## 🎨 UI Features

### Search & Filters
- Search boxes in all list views
- Multi-criteria filtering
- Status/type/date filters
- Real-time search (300ms debounce)

### Status Badges
Color-coded status indicators:
- 🔵 Blue - Info/Open
- 🟠 Orange - Warning/In Progress
- 🔴 Red - Critical/Urgent
- 🟢 Green - Success/Completed
- ⚫ Grey - Inactive/Closed

### Visual Alerts
- 🔴 Red background - Overdue items
- 🟡 Yellow background - Due soon (7 days)
- ⚠️ Warning icons for urgent priorities

### Modals
- Clean form modals for data entry
- View details modals for records
- Large modal size for complex forms
- Auto-focus on first field

---

## 💡 Pro Tips

1. **Use Search:** Every list view has a search box - use it to quickly find records
2. **Check Metrics:** Dashboard cards show key numbers at a glance
3. **Filter First:** Use filters to narrow down data before searching
4. **Priority Matters:** High/urgent items automatically sort to top
5. **Link Records:** Always link rejects to jobs, returns to orders for tracking
6. **Document Everything:** Use notes fields for future reference
7. **Mark PM Complete:** One-click "Mark as Performed" updates next due date automatically
8. **Watch Overdue:** Red highlights indicate overdue tasks - prioritize these
9. **Approval Workflow:** Rejects need manager approval before final disposition
10. **Status Progression:** Follow the status workflow in order for proper tracking

---

## 🔧 System Administration

### Changing Password
1. Contact system administrator
2. Admin updates password in **Employees** module
3. User logs in with new password

### Backing Up Data
- Database: Regular MySQL dumps via Railway dashboard
- Files: Backup entire application folder
- Redis: Configure AOF persistence

### Adding New Products
1. **ADMINISTRATION** → **Products** → **Add Product**
2. Enter product code (SKU), name
3. Select category
4. Add specifications (JSON format)
5. Set reorder level
6. Mark as Active
7. Save product

### Adding New Machines
1. **ADMINISTRATION** → **Machines** → **Add Machine**
2. Enter machine name and code
3. Assign to department
4. Set initial status (Available)
5. Add specifications
6. Record purchase/warranty dates
7. Save machine

---

## 📱 Mobile Access

The system is responsive and works on tablets/phones:
- Touch-friendly buttons (44px minimum)
- Responsive tables
- Mobile-optimized forms
- Works in any modern browser

---

## ⚠️ Troubleshooting

### Can't Log In
- Check username format: `firstname@barron`
- Verify password (case-sensitive)
- Contact admin to verify account is Active

### Permission Denied
- Contact admin to assign proper roles
- Check if you have View/Edit permissions for module

### Data Not Saving
- Check all required fields (marked with *)
- Verify dropdown selections are valid
- Check for duplicate numbers (ticket#, order#, etc.)

### Can't Find Record
- Use search box with partial text
- Clear filters to see all records
- Check date range filters

---

## 📞 Support

**System Administrator:** admin@barron  
**Documentation:** See SYSTEM_DOCUMENTATION.md for full technical details

---

## 🎓 Training Resources

1. **System Overview:** Read full documentation
2. **Hands-On Practice:** Use test data to practice workflows
3. **Module Training:** Complete tasks in each module
4. **Advanced Features:** Explore all filter and search options
5. **Ask Questions:** Contact system admin for help

---

**Version:** 1.0  
**Last Updated:** January 8, 2026  
**Barron (Pty) Ltd - Production Management System**
