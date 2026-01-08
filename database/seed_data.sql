-- ============================================
-- BARRON PRODUCTION MANAGEMENT SYSTEM
-- Seed Data - Initial System Setup
-- ============================================

-- Insert Default Roles
INSERT INTO roles (role_code, role_name, description, is_active) VALUES
('ADMIN', 'System Administrator', 'Full system access and configuration', TRUE),
('MANAGER', 'Department Manager', 'Department management and oversight', TRUE),
('PLANNER', 'Production Planner', 'Production planning and scheduling', TRUE),
('STOCK_PLANNER', 'Stock Planner', 'Stock planning and management', TRUE),
('PLANNING_ASSISTANT', 'Planning Assistant', 'Planning support role', TRUE),
('BRANDING_COORD', 'Branding Coordinator', 'Branding coordination and reject tickets', TRUE),
('SUPERVISOR', 'Supervisor', 'Departmental supervision', TRUE),
('QC_COORD', 'QC Coordinator', 'Quality control and customer returns', TRUE),
('MAINTENANCE_SUPER', 'Maintenance Supervisor', 'Maintenance ticket management', TRUE),
('MAINTENANCE_TECH', 'Maintenance Technician', 'Maintenance execution', TRUE),
('OPERATOR', 'Machine Operator', 'Machine operation and job execution', TRUE),
('APPLIQUE_CUTTER', 'Appliqué Cutter', 'Appliqué cutting operations', TRUE),
('PACKER', 'Packer', 'Packing operations', TRUE),
('FINANCE_USER', 'Finance User', 'Finance and BOM management', TRUE),
('HOD', 'Head of Department', 'Department head with escalation authority', TRUE);

-- Insert Default Permissions
INSERT INTO permissions (permission_code, permission_name, module, is_active) VALUES
-- Master Data Permissions
('master.view', 'View Master Data', 'master_data', TRUE),
('master.create', 'Create Master Data', 'master_data', TRUE),
('master.edit', 'Edit Master Data', 'master_data', TRUE),
('master.delete', 'Delete Master Data', 'master_data', TRUE),
('master.config', 'Configure System', 'master_data', TRUE),

-- Job Planning Permissions
('planning.view', 'View Job Planning', 'job_planning', TRUE),
('planning.create', 'Create Job Schedules', 'job_planning', TRUE),
('planning.edit', 'Edit Job Schedules', 'job_planning', TRUE),
('planning.delete', 'Delete Job Schedules', 'job_planning', TRUE),
('planning.allocate', 'Allocate Jobs to Machines', 'job_planning', TRUE),

-- Defects Permissions
('defects.view', 'View Defects', 'defects', TRUE),
('defects.create', 'Create Defect Tickets', 'defects', TRUE),
('defects.approve', 'Approve Defect Tickets', 'defects', TRUE),
('defects.process', 'Process Replacement Tickets', 'defects', TRUE),
('defects.reports', 'Configure Defect Reports', 'defects', TRUE),

-- SOP Failure Permissions
('sop.view', 'View SOP Failures', 'sop_failure', TRUE),
('sop.create', 'Create SOP Tickets', 'sop_failure', TRUE),
('sop.reject', 'Reject SOP Tickets', 'sop_failure', TRUE),
('sop.reassign', 'Reassign SOP Tickets', 'sop_failure', TRUE),
('sop.ncr', 'Complete NCR', 'sop_failure', TRUE),
('sop.hod_decision', 'HOD Escalation Decision', 'sop_failure', TRUE),

-- Maintenance Permissions
('maintenance.view', 'View Maintenance Tickets', 'maintenance', TRUE),
('maintenance.create', 'Create Maintenance Tickets', 'maintenance', TRUE),
('maintenance.assign', 'Assign Maintenance Tickets', 'maintenance', TRUE),
('maintenance.update', 'Update Maintenance Status', 'maintenance', TRUE),
('maintenance.reports', 'View Maintenance Reports', 'maintenance', TRUE),

-- Finance Permissions
('finance.view_bom', 'View BOM', 'finance', TRUE),
('finance.create_bom', 'Create BOM', 'finance', TRUE),
('finance.edit_bom', 'Edit BOM', 'finance', TRUE),
('finance.reports', 'View Finance Reports', 'finance', TRUE),

-- Operator Permissions
('operator.view_jobs', 'View Assigned Jobs', 'operator', TRUE),
('operator.start_job', 'Start Job', 'operator', TRUE),
('operator.end_job', 'End Job', 'operator', TRUE),
('operator.add_unallocated', 'Add Unallocated Job', 'operator', TRUE),

-- Department Management
('dept.manage_employees', 'Manage Department Employees', 'department', TRUE),
('dept.manage_machines', 'Manage Department Machines', 'department', TRUE),
('dept.allocate_resources', 'Allocate Resources', 'department', TRUE),
('dept.view_reports', 'View Department Reports', 'department', TRUE);

-- Assign Permissions to Admin Role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_code = 'ADMIN';

-- Assign Permissions to Manager Role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'planning.view', 'planning.create', 'planning.edit', 'planning.allocate',
    'defects.view', 'defects.approve',
    'sop.view', 'sop.create',
    'maintenance.view',
    'dept.manage_employees', 'dept.manage_machines', 'dept.allocate_resources', 'dept.view_reports'
)
WHERE r.role_code = 'MANAGER';

-- Assign Permissions to Planner Role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'planning.view', 'planning.create', 'planning.edit', 'planning.allocate',
    'defects.view', 'defects.process'
)
WHERE r.role_code = 'PLANNER';

-- Assign Permissions to QC Coordinator
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'defects.view', 'defects.create', 'defects.reports'
)
WHERE r.role_code = 'QC_COORD';

-- Assign Permissions to Branding Coordinator
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'defects.view', 'defects.create'
)
WHERE r.role_code = 'BRANDING_COORD';

-- Assign Permissions to Maintenance Supervisor
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'maintenance.view', 'maintenance.assign', 'maintenance.update', 'maintenance.reports'
)
WHERE r.role_code = 'MAINTENANCE_SUPER';

-- Assign Permissions to Maintenance Technician
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'maintenance.view', 'maintenance.update'
)
WHERE r.role_code = 'MAINTENANCE_TECH';

-- Assign Permissions to Operator
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'operator.view_jobs', 'operator.start_job', 'operator.end_job', 'operator.add_unallocated'
)
WHERE r.role_code = 'OPERATOR';

-- Assign Permissions to Finance User
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'finance.view_bom', 'finance.create_bom', 'finance.edit_bom', 'finance.reports'
)
WHERE r.role_code = 'FINANCE_USER';

-- Assign Permissions to HOD
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_code IN (
    'planning.view', 'defects.view', 'sop.view', 'sop.hod_decision',
    'maintenance.view', 'dept.view_reports'
)
WHERE r.role_code = 'HOD';

-- Create Default Admin User
INSERT INTO employees (
    employee_number, 
    first_name, 
    last_name, 
    email, 
    username, 
    password_hash, 
    role_id, 
    is_active
)
SELECT 
    'EMP001',
    'System',
    'Administrator',
    'admin@barron.co.za',
    'admin@barron',
    -- Password: admin123 (this should be changed on first login)
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    id,
    TRUE
FROM roles WHERE role_code = 'ADMIN'
LIMIT 1;

-- Insert Sample Departments
INSERT INTO departments (department_code, department_name, daily_target, weekly_target, monthly_target, is_active) VALUES
('EMBR', 'Embroidery', 1000.00, 5000.00, 20000.00, TRUE),
('SCPR', 'Screen Printing', 1500.00, 7500.00, 30000.00, TRUE),
('DTFP', 'DTF Printing', 800.00, 4000.00, 16000.00, TRUE),
('SBLM', 'Sublimation', 600.00, 3000.00, 12000.00, TRUE),
('APPL', 'Appliqué', 500.00, 2500.00, 10000.00, TRUE),
('PACK', 'Packing', 2000.00, 10000.00, 40000.00, TRUE),
('MAINT', 'Maintenance', 0.00, 0.00, 0.00, TRUE),
('QC', 'Quality Control', 0.00, 0.00, 0.00, TRUE);

-- Insert Sample Production Stages for Embroidery
INSERT INTO production_stages (department_id, stage_code, stage_name, stage_order, estimated_duration_hours, is_active)
SELECT id, 'DSGN', 'Design Setup', 1, 2.00, TRUE FROM departments WHERE department_code = 'EMBR'
UNION ALL
SELECT id, 'HOOP', 'Hooping', 2, 0.50, TRUE FROM departments WHERE department_code = 'EMBR'
UNION ALL
SELECT id, 'EMBD', 'Embroidery', 3, 4.00, TRUE FROM departments WHERE department_code = 'EMBR'
UNION ALL
SELECT id, 'TRIM', 'Trimming', 4, 1.00, TRUE FROM departments WHERE department_code = 'EMBR'
UNION ALL
SELECT id, 'QC', 'Quality Check', 5, 0.50, TRUE FROM departments WHERE department_code = 'EMBR';

-- Insert Sample Production Stages for Screen Printing
INSERT INTO production_stages (department_id, stage_code, stage_name, stage_order, estimated_duration_hours, is_active)
SELECT id, 'SCRN', 'Screen Preparation', 1, 3.00, TRUE FROM departments WHERE department_code = 'SCPR'
UNION ALL
SELECT id, 'PRNT', 'Printing', 2, 5.00, TRUE FROM departments WHERE department_code = 'SCPR'
UNION ALL
SELECT id, 'DRY', 'Drying', 3, 2.00, TRUE FROM departments WHERE department_code = 'SCPR'
UNION ALL
SELECT id, 'QC', 'Quality Check', 4, 0.50, TRUE FROM departments WHERE department_code = 'SCPR';

-- Insert Default SLA Rules
INSERT INTO sla_rules (sla_code, sla_name, module, ticket_type, priority, response_time_hours, resolution_time_hours, escalation_rules, is_active) VALUES
('SOP_CRITICAL', 'SOP Failure - Critical', 'sop_failure', 'sop_failure', 'critical', 4, 24, '{"level1": {"hours": 4, "escalate_to": "manager"}, "level2": {"hours": 12, "escalate_to": "hod"}}', TRUE),
('SOP_HIGH', 'SOP Failure - High', 'sop_failure', 'sop_failure', 'high', 8, 48, '{"level1": {"hours": 8, "escalate_to": "manager"}, "level2": {"hours": 24, "escalate_to": "hod"}}', TRUE),
('SOP_NORMAL', 'SOP Failure - Normal', 'sop_failure', 'sop_failure', 'normal', 24, 72, '{"level1": {"hours": 48, "escalate_to": "hod"}}', TRUE),
('MAINT_CRITICAL', 'Maintenance - Critical', 'maintenance', 'maintenance', 'critical', 1, 8, '{"level1": {"hours": 2, "escalate_to": "supervisor"}, "level2": {"hours": 4, "escalate_to": "manager"}}', TRUE),
('MAINT_HIGH', 'Maintenance - High', 'maintenance', 'maintenance', 'high', 4, 24, '{"level1": {"hours": 8, "escalate_to": "supervisor"}}', TRUE),
('MAINT_NORMAL', 'Maintenance - Normal', 'maintenance', 'maintenance', 'normal', 8, 48, '{}', TRUE);

-- Insert System Configuration
INSERT INTO system_config (config_key, config_value, description, is_system) VALUES
('app_name', '"Barron Production Management System"', 'Application name', TRUE),
('app_version', '"1.0.0"', 'Application version', TRUE),
('session_timeout', '3600', 'Session timeout in seconds', FALSE),
('password_min_length', '6', 'Minimum password length', FALSE),
('enable_email_notifications', 'true', 'Enable email notifications', FALSE),
('smtp_config', '{"host": "", "port": 587, "encryption": "tls", "username": "", "password": ""}', 'SMTP configuration', FALSE),
('maintenance_mode', 'false', 'Maintenance mode flag', FALSE),
('auto_logout_operators', '28800', 'Auto logout operators after 8 hours (in seconds)', FALSE);
