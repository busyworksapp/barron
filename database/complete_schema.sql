-- =====================================================
-- Barron Production Management System
-- Complete Database Schema - All Modules
-- Version: 1.1
-- Date: January 8, 2026
-- =====================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET TIME_ZONE = '+00:00';

-- =====================================================
-- AUTHENTICATION & USER MANAGEMENT
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User roles junction table
CREATE TABLE IF NOT EXISTS user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role permissions junction table
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MASTER DATA MODULE
-- =====================================================

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    head_of_department INT,
    production_stages JSON,
    capacity INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_of_department) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    employee_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    hire_date DATE NOT NULL,
    termination_date DATE,
    status ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
    photo_url VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_number (employee_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee departments junction table
CREATE TABLE IF NOT EXISTS employee_departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    department_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_department (employee_id, department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Machines table
CREATE TABLE IF NOT EXISTS machines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    machine_name VARCHAR(100) NOT NULL,
    machine_code VARCHAR(50) UNIQUE NOT NULL,
    department_id INT,
    status ENUM('available', 'in_use', 'maintenance', 'down') DEFAULT 'available',
    purchase_date DATE,
    warranty_expiry DATE,
    last_maintenance_date DATE,
    specifications JSON,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_machine_code (machine_code),
    INDEX idx_status (status),
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(200) NOT NULL,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(100),
    unit_of_measure VARCHAR(20) DEFAULT 'pieces',
    specifications JSON,
    reorder_level INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_code (product_code),
    INDEX idx_category (category),
    INDEX idx_status (status),
    FULLTEXT idx_search (product_name, product_code, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- JOB PLANNING MODULE
-- =====================================================

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    order_date DATE NOT NULL,
    due_date DATE NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'confirmed', 'in_production', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_customer (customer_name),
    INDEX idx_dates (order_date, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_number VARCHAR(50) UNIQUE NOT NULL,
    order_id INT,
    product_id INT NOT NULL,
    department_id INT,
    quantity_planned INT NOT NULL,
    quantity_completed INT DEFAULT 0,
    machine_id INT,
    assigned_operator_id INT,
    start_date DATETIME,
    end_date DATETIME,
    setup_time INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'on_hold', 'cancelled', 'failed') DEFAULT 'scheduled',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_operator_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_job_number (job_number),
    INDEX idx_status (status),
    INDEX idx_order (order_id),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Production logs table
CREATE TABLE IF NOT EXISTS production_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    production_date DATE NOT NULL,
    shift VARCHAR(20),
    quantity_produced INT NOT NULL,
    quantity_scrapped INT DEFAULT 0,
    operator_notes TEXT,
    logged_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_job (job_id),
    INDEX idx_date (production_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFECTS & QUALITY MODULE
-- =====================================================

-- Internal rejects table
CREATE TABLE IF NOT EXISTS internal_rejects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reject_number VARCHAR(50) UNIQUE NOT NULL,
    job_id INT,
    product_id INT NOT NULL,
    department_id INT,
    quantity_rejected INT NOT NULL,
    defect_type ENUM('dimensional', 'surface', 'material', 'assembly', 'contamination', 'incomplete', 'packaging', 'testing', 'other') NOT NULL,
    defect_description TEXT NOT NULL,
    disposition ENUM('scrap', 'rework', 'use_as_is', 'return_supplier') NOT NULL,
    root_cause TEXT,
    corrective_action TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approver_notes TEXT,
    approved_by INT,
    approval_date DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reject_number (reject_number),
    INDEX idx_status (status),
    INDEX idx_job (job_id),
    INDEX idx_defect_type (defect_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer returns table
CREATE TABLE IF NOT EXISTS customer_returns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rma_number VARCHAR(50) UNIQUE NOT NULL,
    order_id INT,
    product_id INT NOT NULL,
    quantity_returned INT NOT NULL,
    return_reason ENUM('defective', 'wrong_item', 'damaged_shipping', 'not_as_described', 'quality_issue', 'customer_error', 'late_delivery', 'other') NOT NULL,
    customer_complaint TEXT,
    investigation_notes TEXT,
    resolution_type ENUM('refund', 'replacement', 'credit', 'repair', 'no_action'),
    resolution_notes TEXT,
    refund_amount DECIMAL(10,2),
    restocking_fee DECIMAL(10,2),
    status ENUM('received', 'investigating', 'approved', 'rejected', 'resolved') DEFAULT 'received',
    resolved_by INT,
    resolution_date DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rma_number (rma_number),
    INDEX idx_status (status),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COMPLIANCE MODULE (SOP & NCR)
-- =====================================================

-- SOP failures table
CREATE TABLE IF NOT EXISTS sop_failures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    sop_reference VARCHAR(100) NOT NULL,
    department_id INT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    failure_description TEXT NOT NULL,
    immediate_action TEXT,
    root_cause TEXT,
    corrective_action TEXT,
    assigned_to INT,
    target_closure_date DATE,
    status ENUM('open', 'investigating', 'action_required', 'resolved', 'closed') DEFAULT 'open',
    closed_by INT,
    closed_at DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status (status),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NCR reports table
CREATE TABLE IF NOT EXISTS ncr_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ncr_number VARCHAR(50) UNIQUE NOT NULL,
    ncr_type ENUM('internal', 'supplier', 'customer') NOT NULL,
    department_id INT,
    date_raised DATE NOT NULL,
    description TEXT NOT NULL,
    immediate_action TEXT,
    root_cause TEXT,
    corrective_action TEXT,
    preventive_action TEXT,
    verification_notes TEXT,
    assigned_to INT,
    target_closure_date DATE,
    status ENUM('open', 'investigation', 'capa_pending', 'capa_in_progress', 'verification', 'closed') DEFAULT 'open',
    closed_by INT,
    closed_at DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ncr_number (ncr_number),
    INDEX idx_status (status),
    INDEX idx_type (ncr_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MAINTENANCE MODULE
-- =====================================================

-- Maintenance tickets table
CREATE TABLE IF NOT EXISTS maintenance_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    machine_id INT NOT NULL,
    maintenance_type ENUM('breakdown', 'preventive', 'inspection', 'calibration') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    issue_description TEXT NOT NULL,
    work_performed TEXT,
    assigned_to INT,
    scheduled_date DATETIME,
    completed_date DATETIME,
    downtime_hours DECIMAL(5,2),
    cost DECIMAL(10,2),
    parts_used TEXT,
    status ENUM('open', 'assigned', 'in_progress', 'on_hold', 'completed', 'closed') DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status (status),
    INDEX idx_machine (machine_id),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preventive maintenance schedules table
CREATE TABLE IF NOT EXISTS preventive_maintenance_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_name VARCHAR(200) NOT NULL,
    machine_id INT NOT NULL,
    task_description TEXT,
    frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual') NOT NULL,
    estimated_duration DECIMAL(4,2),
    last_performed_date DATE,
    next_due_date DATE NOT NULL,
    checklist_items TEXT,
    assigned_to INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_machine (machine_id),
    INDEX idx_status (status),
    INDEX idx_next_due (next_due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FINANCE MODULE (BOM)
-- =====================================================

-- BOM master table
CREATE TABLE IF NOT EXISTS bom (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bom_number VARCHAR(50) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    status ENUM('draft', 'active', 'obsolete') DEFAULT 'draft',
    description TEXT,
    overhead_percentage DECIMAL(5,2) DEFAULT 0,
    total_cost DECIMAL(12,2) NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_bom_number (bom_number),
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOM components table
CREATE TABLE IF NOT EXISTS bom_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bom_id INT NOT NULL,
    component_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bom_id) REFERENCES bom(id) ON DELETE CASCADE,
    INDEX idx_bom (bom_id),
    INDEX idx_component (component_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INITIAL DATA - DEFAULT ADMIN USER & PERMISSIONS
-- =====================================================

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('master.view', 'View master data'),
('master.edit', 'Edit master data'),
('planning.view', 'View job planning'),
('planning.edit', 'Edit job planning'),
('production.view', 'View production data'),
('production.edit', 'Log production'),
('defects.view', 'View defects'),
('defects.edit', 'Edit defects'),
('defects.approve', 'Approve rejects'),
('sop.view', 'View compliance data'),
('sop.edit', 'Edit compliance data'),
('maintenance.view', 'View maintenance'),
('maintenance.edit', 'Edit maintenance'),
('finance.view_bom', 'View Bill of Materials'),
('finance.edit_bom', 'Create and edit BOMs'),
('operator.view_jobs', 'View assigned jobs'),
('reports.view', 'View reports')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert default admin role
INSERT INTO roles (name, description) VALUES
('Administrator', 'Full system access with all permissions')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Get admin role ID
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1);

-- Assign all permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin_role_id, id FROM permissions
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Insert default admin user
-- Password: admin123 (bcrypt hash with cost 12)
INSERT INTO users (username, password, email, first_name, last_name, status)
VALUES ('admin@barron', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5oPLF.8/.vqCu', 'admin@barron.com', 'Admin', 'User', 'active')
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Get admin user ID
SET @admin_user_id = (SELECT id FROM users WHERE username = 'admin@barron' LIMIT 1);

-- Assign admin role to admin user
INSERT INTO user_roles (user_id, role_id)
VALUES (@admin_user_id, @admin_role_id)
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);

-- =====================================================
-- SAMPLE DATA (Optional - Comment out for production)
-- =====================================================

/*
-- Sample Department
INSERT INTO departments (name, code, description, capacity, status, created_by) VALUES
('Assembly', 'ASM', 'Final product assembly department', 10, 'active', 1);

-- Sample Product
INSERT INTO products (product_name, product_code, category, unit_of_measure, status, created_by) VALUES
('Widget Assembly', 'WDG-001', 'Finished Goods', 'pieces', 'active', 1);

-- Sample Machine
INSERT INTO machines (machine_name, machine_code, department_id, status, created_by) VALUES
('CNC Machine 1', 'CNC-001', 1, 'available', 1);
*/

-- =====================================================
-- DATABASE OPTIMIZATION
-- =====================================================

-- Optimize all tables
OPTIMIZE TABLE users, roles, permissions, user_roles, role_permissions, activity_logs,
    departments, employees, employee_departments, machines, products,
    orders, order_items, jobs, production_logs,
    internal_rejects, customer_returns,
    sop_failures, ncr_reports,
    maintenance_tickets, preventive_maintenance_schedules,
    bom, bom_components;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT 'Database schema created successfully!' AS Status;
SELECT COUNT(*) AS 'Total Tables' FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT 'Default admin user: admin@barron' AS 'Login';
SELECT 'Default admin password: admin123 (CHANGE IMMEDIATELY!)' AS 'Security';
