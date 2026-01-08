# Barron Production Management System
## Complete System Documentation

**Version:** 1.0  
**Date:** January 8, 2026  
**Developer:** Barron (Pty) Ltd Development Team

---

## System Overview

The Barron Production Management System is a comprehensive enterprise resource planning (ERP) solution designed for manufacturing operations. It provides end-to-end management of production processes, quality control, maintenance, and compliance.

### Technology Stack
- **Backend:** PHP 8.x with PDO
- **Database:** MySQL 8.x (Railway Cloud)
- **Cache/Sessions:** Redis (Railway Cloud)
- **Frontend:** Pure HTML5, CSS3, Vanilla JavaScript
- **Architecture:** RESTful API, Role-Based Access Control (RBAC)

### Database Credentials
- **Host:** yamanote.proxy.rlwy.net:39713
- **Database:** railway
- **User:** root
- **Password:** hwemqHyJCOMkVycHiOcRqWBXnUryhFjw

### Redis Credentials
- **Host:** caboose.proxy.rlwy.net:39766
- **Password:** maXFCPazHpxaASnHpDcszQQpTsfONXFE

### Default Admin Access
- **Username:** admin@barron
- **Password:** admin123

---

## System Modules

### 1. AUTHENTICATION & AUTHORIZATION
**Files:** `login.php`, `logout.php`, `classes/Auth.php`

**Features:**
- Bcrypt password hashing
- Redis-based session management
- 15 role-based permissions
- Username format: firstname@barron
- Session timeout: 30 minutes
- Activity logging

**Permissions:**
- master.view, master.edit
- planning.view, planning.edit
- production.view, production.edit
- defects.view, defects.edit, defects.approve
- sop.view, sop.edit
- maintenance.view, maintenance.edit
- finance.view_bom
- operator.view_jobs

---

### 2. MASTER DATA MANAGEMENT

#### 2.1 Departments (`modules/master/departments.php`)
**APIs:** 5 endpoints (stats, list, get, create, update)

**Features:**
- Department hierarchy
- Production stages management (JSON array)
- Department capacity planning
- Active/inactive status
- HOD assignment

**Key Fields:**
- name, code, description
- head_of_department (FK to users)
- production_stages (JSON)
- capacity, status

#### 2.2 Employees (`modules/master/employees.php`)
**APIs:** 5 endpoints

**Features:**
- Employee profiles with photo
- Multi-department assignment (many-to-many)
- Role-based access assignment
- Automatic username generation
- Hire/termination date tracking
- Contact information

**Key Fields:**
- first_name, last_name, email, phone
- username (auto: firstname@barron)
- employee_number, hire_date
- status (active/on_leave/terminated)

#### 2.3 Machines (`modules/master/machines.php`)
**APIs:** 5 endpoints

**Features:**
- Equipment registry
- Department assignment
- 4 status states (available/in_use/maintenance/down)
- Maintenance schedule tracking
- Specifications (JSON)
- Purchase and warranty tracking

**Key Fields:**
- machine_name, machine_code
- department_id, status
- purchase_date, warranty_expiry
- specifications (JSON)
- last_maintenance_date

#### 2.4 Products (`modules/master/products.php`)
**APIs:** 6 endpoints (includes search)

**Features:**
- Product catalog with SKU
- Category management
- Specifications (JSON)
- BOM placeholder
- Active/inactive status
- Full-text search

**Key Fields:**
- product_name, product_code (SKU)
- category, unit_of_measure
- specifications (JSON)
- reorder_level, status

---

### 3. JOB PLANNING MODULE

#### 3.1 Orders Management (`modules/planning/orders.php`)
**APIs:** 5 endpoints

**Features:**
- Multi-item order support
- Order items table (one-to-many)
- 5 status workflow (pending → confirmed → in_production → completed → cancelled)
- Customer information
- Due date tracking
- Priority management

**Key Fields:**
- order_number, customer_name, customer_email
- order_date, due_date, priority
- status, total_items
- order_items: product_id, quantity, unit_price

#### 3.2 Job Scheduling (`modules/planning/schedule.php`)
**APIs:** 5 endpoints

**Features:**
- Production job creation from orders
- Machine and operator assignment
- Resource capacity validation
- Start/end date planning
- Setup time tracking
- 6 status states

**Key Fields:**
- job_number (AUTO: JOB202601xxxx)
- order_id, product_id, quantity_planned
- machine_id, assigned_operator_id
- start_date, end_date, setup_time
- status (scheduled/in_progress/completed/on_hold/cancelled/failed)

#### 3.3 Production Tracking (`modules/planning/tracking.php`)
**APIs:** 6 endpoints (includes log production)

**Features:**
- Real-time production logging
- Progress tracking (quantity produced vs planned)
- Scrap/waste tracking
- Operator notes
- Status updates
- Completion percentage calculation

**Key Fields:**
- job_id, production_date, shift
- quantity_produced, quantity_scrapped
- operator_notes, logged_by
- Calculated: progress percentage

---

### 4. DEFECTS & QUALITY MODULE

#### 4.1 Internal Rejects (`modules/defects/internal_rejects.php`)
**APIs:** 6 endpoints (includes approve)

**Features:**
- Production defect tracking
- 9 defect types (dimensional/surface/material/assembly/contamination/incomplete/packaging/testing/other)
- 4 dispositions (scrap/rework/use_as_is/return_supplier)
- Approval workflow (pending → approved/rejected)
- Quantity validation (≤ produced quantity)
- Reject rate calculation
- Department-level tracking

**Key Fields:**
- reject_number (AUTO: REJ202601xxxx)
- job_id, product_id, department_id
- quantity_rejected, defect_type, disposition
- status (pending/approved/rejected)
- approver_notes, approved_by, approval_date

**Metrics:**
- Pending count, approved count, this month
- Reject rate % (rejected/produced × 100)

#### 4.2 Customer Returns (`modules/defects/customer_returns.php`)
**APIs:** 5 endpoints

**Features:**
- RMA tracking system
- Order/product integration
- 8 return reasons (defective/wrong_item/damaged_shipping/not_as_described/quality_issue/customer_error/late_delivery/other)
- 5 status workflow (received → investigating → approved/rejected → resolved)
- 5 resolution types (refund/replacement/credit/repair/no_action)
- Financial tracking (refund amount, restocking fee)
- Customer complaint documentation

**Key Fields:**
- rma_number (AUTO: RMA202601xxxx)
- order_id, product_id, quantity_returned
- return_reason, customer_complaint
- investigation_notes, resolution_type
- refund_amount, restocking_fee
- resolution_notes, resolved_by

**Metrics:**
- Open returns, resolved count, this month
- Return rate % (returned orders/total orders × 100)

---

### 5. COMPLIANCE MODULE (SOP & NCR)

#### 5.1 SOP Failure Tickets (`modules/sop/tickets.php`)
**APIs:** 5 endpoints

**Features:**
- Standard Operating Procedure violation tracking
- 4 severity levels (low/medium/high/critical)
- 5 status states (open → investigating → action_required → resolved → closed)
- Root cause analysis documentation
- Corrective action planning
- Department assignment
- Target closure dates

**Key Fields:**
- ticket_number (AUTO: SOP202601xxxx)
- sop_reference, department_id
- severity, failure_description
- immediate_action, root_cause
- corrective_action, assigned_to
- target_closure_date, closed_by

**Metrics:**
- Open tickets, resolved count, this month
- Critical severity count

#### 5.2 NCR Reports (`modules/sop/ncr.php`)
**APIs:** 5 endpoints

**Features:**
- Non-Conformance Report system
- 3 NCR types (internal/supplier/customer)
- 6 CAPA workflow states (open → investigation → capa_pending → capa_in_progress → verification → closed)
- Root cause analysis
- Corrective AND Preventive actions (CAPA)
- Effectiveness verification
- Overdue tracking with visual alerts

**Key Fields:**
- ncr_number (AUTO: NCR202601xxxx)
- ncr_type, department_id, date_raised
- description, immediate_action
- root_cause, corrective_action, preventive_action
- verification_notes, assigned_to
- target_closure_date, closed_by

**Metrics:**
- Open NCRs, closed count, this month
- Overdue CAPA count

---

### 6. MAINTENANCE MODULE

#### 6.1 Maintenance Tickets (`modules/maintenance/tickets.php`)
**APIs:** 5 endpoints

**Features:**
- Work order management
- 4 maintenance types (breakdown/preventive/inspection/calibration)
- 4 priority levels (low/normal/high/urgent)
- 6 status workflow (open → assigned → in_progress → on_hold → completed → closed)
- Machine status integration (auto-update)
- Downtime tracking (hours)
- Cost tracking (parts + labor)
- Technician assignment
- Parts inventory logging

**Key Fields:**
- ticket_number (AUTO: MNT202601xxxx)
- machine_id, maintenance_type, priority
- issue_description, work_performed
- assigned_to, scheduled_date, completed_date
- downtime_hours, cost, parts_used

**Metrics:**
- Open tickets, in-progress count
- Completed count, urgent priority count

**Smart Features:**
- Breakdown tickets set machine status to "down"
- Completed tickets restore machine to "available"

#### 6.2 Preventive Maintenance Schedule (`modules/maintenance/schedule.php`)
**APIs:** 6 endpoints (includes mark_performed)

**Features:**
- Recurring maintenance schedules
- 6 frequency options (daily/weekly/monthly/quarterly/semi_annual/annual)
- Automatic next due date calculation
- Overdue visual alerts (red background)
- Due soon alerts (yellow background, 7-day warning)
- Task checklists
- Duration estimation
- One-click "Mark as Performed"
- Active/inactive scheduling

**Key Fields:**
- task_name, machine_id, task_description
- frequency, estimated_duration
- last_performed_date, next_due_date
- checklist_items (text, line-separated)
- assigned_to, status

**Metrics:**
- Active schedules count
- Overdue tasks count
- Due this week count
- Completed this month count

**Smart Features:**
- Auto-calculate next due based on frequency when marked performed
- Visual overdue warnings
- Visual due-soon warnings (within 7 days)

---

## Database Schema

### Core Tables (30+)
1. **users** - User accounts and authentication
2. **roles** - Role definitions
3. **permissions** - Permission definitions
4. **user_roles** - User-role assignments (many-to-many)
5. **departments** - Department master
6. **employees** - Employee profiles
7. **employee_departments** - Employee-department assignments (many-to-many)
8. **machines** - Equipment master
9. **products** - Product catalog
10. **orders** - Customer orders
11. **order_items** - Order line items
12. **jobs** - Production jobs
13. **production_logs** - Production tracking entries
14. **internal_rejects** - Production defects
15. **customer_returns** - RMA tracking
16. **sop_failures** - SOP violation tickets
17. **ncr_reports** - Non-conformance reports
18. **maintenance_tickets** - Work orders
19. **preventive_maintenance_schedules** - PM schedules
20. **bom** - Bill of Materials master
21. **bom_components** - BOM component items
22. **activity_logs** - System audit trail

### Key Relationships
- Orders → Order Items (1:many)
- Orders → Jobs (1:many)
- Jobs → Production Logs (1:many)
- Jobs → Internal Rejects (1:many)
- Orders → Customer Returns (1:many)
- Machines → Maintenance Tickets (1:many)
- Machines → PM Schedules (1:many)
- Products → BOM (1:many)
- BOM → BOM Components (1:many)
- Departments → Employees (many:many via junction)
- Users → Multiple entities (created_by, assigned_to, approved_by, etc.)

---

## API Structure

### Standard Response Format
```json
{
  "success": true|false,
  "message": "Operation result message",
  "data": {} | []
}
```

### Common API Endpoints Pattern
Each module follows consistent patterns:
- **stats.php** - Dashboard statistics
- **list.php** - Filtered list with search
- **get.php** - Single record details
- **create.php** - Insert new record
- **update.php** - Modify existing record
- **[custom].php** - Special operations (approve, mark_performed, etc.)

### API Authentication
All APIs require:
- Valid session (checked via Auth class)
- Appropriate permission (hasPermission check)
- Returns 401/403 on auth failure

### Transaction Handling
Create/update operations use PDO transactions:
- Begin transaction
- Validate data
- Execute inserts/updates
- Log activity
- Commit or rollback on error

---

## Frontend Architecture

### File Structure
```
assets/
  css/
    industrial.css    - Main UI framework (600+ lines)
    master.css        - Module-specific styles
    dashboard.css     - Dashboard styles
  js/
    [module].js       - Module-specific logic
    
modules/
  master/             - Master data pages
  planning/           - Job planning pages
  defects/            - Quality pages
  sop/                - Compliance pages
  maintenance/        - Maintenance pages

api/
  [module]/
    [endpoint].php    - API endpoints

includes/
  header.php          - Page header with nav
  sidebar.php         - Navigation sidebar
  footer.php          - Page footer

config/
  config.php          - Database config
  
classes/
  Auth.php            - Authentication logic
```

### UI Components
- **Modal System** - Reusable form modals
- **Data Tables** - Sortable, searchable tables
- **Filter Sections** - Multi-criteria filtering
- **Badge System** - Status/severity indicators
- **Stat Cards** - Dashboard metrics
- **Form Controls** - Consistent 44px+ touch targets

### Design Standards
- Industrial color palette
- 44px minimum touch target
- Mobile-responsive layout
- Accessibility-compliant
- Consistent spacing (16px base unit)

---

## Security Features

### Authentication
- Bcrypt password hashing (cost: 12)
- Secure session management via Redis
- CSRF protection ready
- Session timeout (30 minutes)

### Authorization
- Role-Based Access Control (RBAC)
- Granular permissions (15 types)
- Page-level access control
- API-level permission checks

### Data Validation
- Server-side validation on all inputs
- PDO prepared statements (SQL injection prevention)
- XSS prevention via escapeHtml functions
- Duplicate prevention checks
- Foreign key validation

### Audit Trail
- Activity logging for all operations
- User tracking (created_by, updated_by)
- Timestamp tracking (created_at, updated_at)
- Approval tracking (approved_by, approval_date)

---

## Key Features Summary

### Automation
✅ Automatic username generation (firstname@barron)  
✅ Auto-incrementing ticket numbers (REJ/RMA/SOP/NCR/MNT)  
✅ Job number generation (JOB202601xxxx)  
✅ Next due date calculation for PM schedules  
✅ Machine status updates on ticket completion  
✅ Progress percentage calculation  

### Validation
✅ Reject quantity ≤ produced quantity  
✅ Resource capacity validation  
✅ Duplicate prevention (ticket numbers, usernames, etc.)  
✅ Date range validation  
✅ Foreign key existence checks  

### Workflow Management
✅ Multi-state status workflows (5-6 states per module)  
✅ Approval processes (rejects, NCRs)  
✅ Assignment tracking  
✅ Target date monitoring  
✅ Overdue alerts with visual warnings  

### Reporting Metrics
✅ Real-time dashboard statistics  
✅ Reject rate calculation  
✅ Return rate calculation  
✅ Overdue tracking  
✅ Completion percentages  
✅ Monthly trend analysis  

### Integration
✅ Order → Job → Production flow  
✅ Order → Return integration  
✅ Job → Reject integration  
✅ Machine → Maintenance integration  
✅ Cross-module foreign key relationships  

---

## Performance Optimizations

### Database
- Indexed foreign keys
- Prepared statements (cached)
- Efficient JOIN queries
- Selective column retrieval

### Caching
- Redis session storage
- Query result caching ready
- Static asset caching

### Frontend
- Debounced search (300ms)
- Lazy loading ready
- Minimal JavaScript dependencies
- CSS sprite system ready

---

## Deployment Notes

### Requirements
- PHP 8.0+
- MySQL 8.0+
- Redis 6.0+
- Apache/Nginx with mod_rewrite
- SSL certificate (recommended)

### Configuration Steps
1. Update `config/config.php` with database credentials
2. Import database schema
3. Configure Redis connection
4. Set BASE_URL constant
5. Configure session parameters
6. Set file upload permissions
7. Enable error logging

### Production Checklist
- [ ] Disable display_errors
- [ ] Enable error logging
- [ ] Set secure session cookies
- [ ] Implement HTTPS
- [ ] Configure backup schedule
- [ ] Set up monitoring
- [ ] Optimize database indexes
- [ ] Configure Redis persistence
- [ ] Set up rate limiting
- [ ] Implement CSRF tokens

---

## Module Statistics

| Module | Pages | APIs | Features |
|--------|-------|------|----------|
| Authentication | 2 | 0 | Login, Logout, RBAC |
| Master Data | 4 | 21 | Depts, Employees, Machines, Products |
| Job Planning | 3 | 16 | Orders, Scheduling, Tracking |
| Defects | 2 | 11 | Rejects, Returns |
| Compliance | 2 | 10 | SOP Tickets, NCRs |
| Maintenance | 2 | 11 | Tickets, PM Schedules |
| Finance | 1 | 5 | Bill of Materials, Cost Analysis |
| **Total** | **16** | **74** | **22+ subsystems** |

---

## Support & Maintenance

### Common Tasks
- **Add User:** Use employees module, assign roles
- **Create Order:** Orders → Create → Add items → Schedule jobs
- **Log Defect:** Internal Rejects → Create → Link to job
- **Schedule PM:** PM Schedule → Create → Set frequency
- **Handle Customer Return:** Customer Returns → Create → Link to order

### Troubleshooting
- **Login Issues:** Check Redis connection, verify user active status
- **Permission Errors:** Verify user roles and permissions
- **API Errors:** Check error logs, validate input data
- **Session Timeout:** Increase timeout in config.php

### Future Enhancements
- Bill of Materials (BOM) module
- Finance/Costing module
- Reporting & Analytics dashboard
- Mobile app for operators
- Real-time notifications
- Document management
- Supplier management
- Inventory control

---

### 7. FINANCE MODULE (BOM)

#### 7.1 Bill of Materials (`modules/finance/bom.php`)
**APIs:** 5 endpoints

**Features:**
- Multi-level BOM structure
- Dynamic component management
- Automatic cost calculation
- 3 status states (draft/active/obsolete)
- Version control
- Material cost tracking
- Overhead percentage calculation
- Component quantity and unit management
- 6 unit types (pieces/kg/meters/liters/boxes/sets)

**Key Fields:**
- bom_number (AUTO: BOM202601xxxx)
- product_id, version, status
- overhead_percentage, total_cost
- description, notes

**BOM Components:**
- component_name (part number/description)
- quantity, unit, unit_cost, total_cost

**Metrics:**
- Active BOMs count
- Approved BOMs count
- Draft BOMs count
- Average BOM cost

**Smart Features:**
- Real-time cost aggregation (material + overhead)
- Dynamic component add/remove
- Automatic total cost calculation
- Version comparison support
- Component-level cost breakdown

---

## Contact & Credits

**Developed for:** Barron (Pty) Ltd  
**System Name:** Barron Production Management System  
**Version:** 1.1  
**Release Date:** January 8, 2026

**Technical Support:** admin@barron  
**Documentation:** This file + inline code comments

---

*End of Documentation*
