-- ============================================
-- BARRON PRODUCTION MANAGEMENT SYSTEM
-- Database Schema v1.0
-- ============================================

-- Core System Tables
-- ============================================

-- Departments
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(50) UNIQUE NOT NULL,
    department_name VARCHAR(255) NOT NULL,
    daily_target DECIMAL(10,2) DEFAULT 0,
    weekly_target DECIMAL(10,2) DEFAULT 0,
    monthly_target DECIMAL(10,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_dept_code (department_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Production Stages (per department)
CREATE TABLE IF NOT EXISTS production_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    stage_code VARCHAR(50) NOT NULL,
    stage_name VARCHAR(255) NOT NULL,
    stage_order INT NOT NULL,
    estimated_duration_hours DECIMAL(5,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dept_stage (department_id, stage_code),
    INDEX idx_dept (department_id),
    INDEX idx_order (stage_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(50) UNIQUE NOT NULL,
    role_name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_code (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_code VARCHAR(100) UNIQUE NOT NULL,
    permission_name VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permission_code (permission_code),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions (Many-to-Many)
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    department_id INT,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_emp_number (employee_number),
    INDEX idx_username (username),
    INDEX idx_dept (department_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee Department Assignment (Many-to-Many)
CREATE TABLE IF NOT EXISTS employee_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    department_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_emp_dept (employee_id, department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Machines
CREATE TABLE IF NOT EXISTS machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_code VARCHAR(50) UNIQUE NOT NULL,
    machine_name VARCHAR(255) NOT NULL,
    machine_number VARCHAR(50),
    department_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    status ENUM('available', 'in_use', 'maintenance', 'down') DEFAULT 'available',
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_machine_code (machine_code),
    INDEX idx_dept (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products / Items Master
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(100) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    specifications JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_product_code (product_code),
    INDEX idx_active (is_active),
    FULLTEXT idx_search (product_name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders / Jobs
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(100) UNIQUE NOT NULL,
    sales_order_number VARCHAR(100),
    customer_name VARCHAR(255) NOT NULL,
    order_value DECIMAL(12,2),
    start_date DATE,
    end_date DATE,
    status ENUM('pending', 'scheduled', 'in_progress', 'on_hold', 'completed', 'rejected', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    notes TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_order_number (order_number),
    INDEX idx_sales_order (sales_order_number),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_customer (customer_name(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    completed_quantity INT DEFAULT 0,
    rejected_quantity INT DEFAULT 0,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(12,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Planning / Scheduling
CREATE TABLE IF NOT EXISTS job_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    department_id INT NOT NULL,
    planned_start_date DATE NOT NULL,
    planned_end_date DATE NOT NULL,
    actual_start_date DATE,
    actual_end_date DATE,
    status ENUM('scheduled', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'scheduled',
    assigned_machine_id INT,
    assigned_operator_id INT,
    production_stages JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_machine_id) REFERENCES machines(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_operator_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_dept (department_id),
    INDEX idx_dates (planned_start_date, planned_end_date),
    INDEX idx_status (status),
    INDEX idx_machine (assigned_machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Production Tracking
CREATE TABLE IF NOT EXISTS job_production_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_schedule_id INT NOT NULL,
    order_id INT NOT NULL,
    employee_id INT NOT NULL,
    machine_id INT,
    production_stage_id INT,
    department_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    quantity_completed INT DEFAULT 0,
    status ENUM('started', 'paused', 'completed') DEFAULT 'started',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_schedule_id) REFERENCES job_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE SET NULL,
    FOREIGN KEY (production_stage_id) REFERENCES production_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    INDEX idx_job (job_schedule_id),
    INDEX idx_order (order_id),
    INDEX idx_employee (employee_id),
    INDEX idx_times (start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internal Rejects (Replacement Tickets)
CREATE TABLE IF NOT EXISTS internal_rejects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(100) UNIQUE NOT NULL,
    order_id INT NOT NULL,
    order_item_id INT,
    department_id INT NOT NULL,
    production_stage_id INT,
    quantity_rejected INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending_approval', 'approved', 'rejected', 'replacement_processed', 'no_stock') DEFAULT 'pending_approval',
    submitted_by INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    processed_by INT,
    processed_at TIMESTAMP NULL,
    notes TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (production_stage_id) REFERENCES production_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (submitted_by) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_ticket (ticket_number),
    INDEX idx_order (order_id),
    INDEX idx_dept (department_id),
    INDEX idx_status (status),
    INDEX idx_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Returns
CREATE TABLE IF NOT EXISTS customer_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_number VARCHAR(100) UNIQUE NOT NULL,
    order_id INT NOT NULL,
    order_item_id INT,
    quantity_returned INT NOT NULL,
    return_reason TEXT NOT NULL,
    defect_description TEXT,
    return_date DATE NOT NULL,
    recorded_by INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES employees(id) ON DELETE RESTRICT,
    INDEX idx_return (return_number),
    INDEX idx_order (order_id),
    INDEX idx_date (return_date),
    INDEX idx_recorded (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOP Failure Tickets
CREATE TABLE IF NOT EXISTS sop_failures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(100) UNIQUE NOT NULL,
    sop_reference VARCHAR(255) NOT NULL,
    failure_description TEXT NOT NULL,
    impact_description TEXT,
    charging_department_id INT NOT NULL,
    charged_department_id INT NOT NULL,
    current_department_id INT NOT NULL,
    status ENUM('open', 'ncr_in_progress', 'ncr_completed', 'rejected', 'escalated', 'closed') DEFAULT 'open',
    rejection_reason TEXT,
    reassignment_reason TEXT,
    ncr_details JSON,
    hod_decision TEXT,
    raised_by INT NOT NULL,
    raised_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    sla_due_date TIMESTAMP,
    is_overdue BOOLEAN DEFAULT FALSE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (charging_department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (charged_department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (current_department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (raised_by) REFERENCES employees(id) ON DELETE RESTRICT,
    INDEX idx_ticket (ticket_number),
    INDEX idx_charged_dept (charged_department_id),
    INDEX idx_current_dept (current_department_id),
    INDEX idx_status (status),
    INDEX idx_sla (sla_due_date, is_overdue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOP Ticket History / Audit Trail
CREATE TABLE IF NOT EXISTS sop_ticket_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    action ENUM('created', 'rejected', 'reassigned', 'ncr_started', 'ncr_completed', 'escalated', 'hod_decision', 'closed') NOT NULL,
    from_department_id INT,
    to_department_id INT,
    performed_by INT NOT NULL,
    reason TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES sop_failures(id) ON DELETE CASCADE,
    FOREIGN KEY (from_department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (to_department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES employees(id) ON DELETE RESTRICT,
    INDEX idx_ticket (ticket_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance Tickets
CREATE TABLE IF NOT EXISTS maintenance_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(100) UNIQUE NOT NULL,
    machine_id INT NOT NULL,
    department_id INT NOT NULL,
    issue_description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'assigned', 'in_progress', 'awaiting_parts', 'completed', 'cancelled') DEFAULT 'open',
    reported_by INT NOT NULL,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to INT,
    assigned_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expected_completion TIMESTAMP,
    resolution_notes TEXT,
    downtime_hours DECIMAL(5,2),
    sla_priority VARCHAR(50),
    is_preventive BOOLEAN DEFAULT FALSE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (reported_by) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_ticket (ticket_number),
    INDEX idx_machine (machine_id),
    INDEX idx_dept (department_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_reported (reported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bill of Materials (BOM)
CREATE TABLE IF NOT EXISTS bill_of_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bom_code VARCHAR(100) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    version VARCHAR(50) DEFAULT '1.0',
    is_active BOOLEAN DEFAULT TRUE,
    effective_date DATE,
    obsolete_date DATE,
    total_material_cost DECIMAL(12,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_bom_code (bom_code),
    INDEX idx_product (product_id),
    INDEX idx_active (is_active),
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOM Items (Materials/Components)
CREATE TABLE IF NOT EXISTS bom_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bom_id INT NOT NULL,
    material_code VARCHAR(100) NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,4) NOT NULL,
    unit_of_measure VARCHAR(50),
    unit_cost DECIMAL(10,2),
    total_cost DECIMAL(12,2),
    specifications TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bom_id) REFERENCES bill_of_materials(id) ON DELETE CASCADE,
    INDEX idx_bom (bom_id),
    INDEX idx_material (material_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dynamic Forms Configuration
CREATE TABLE IF NOT EXISTS dynamic_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_code VARCHAR(100) UNIQUE NOT NULL,
    form_name VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    description TEXT,
    form_config JSON NOT NULL,
    validation_rules JSON,
    is_active BOOLEAN DEFAULT TRUE,
    version VARCHAR(50) DEFAULT '1.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_form_code (form_code),
    INDEX idx_module (module),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow Rules Configuration
CREATE TABLE IF NOT EXISTS workflow_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_code VARCHAR(100) UNIQUE NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    department_id INT,
    trigger_event VARCHAR(100) NOT NULL,
    conditions JSON,
    actions JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_rule_code (rule_code),
    INDEX idx_module (module),
    INDEX idx_trigger (trigger_event),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SLA Configuration
CREATE TABLE IF NOT EXISTS sla_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sla_code VARCHAR(100) UNIQUE NOT NULL,
    sla_name VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    ticket_type VARCHAR(100),
    priority VARCHAR(50),
    response_time_hours INT,
    resolution_time_hours INT,
    escalation_rules JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_sla_code (sla_code),
    INDEX idx_module (module),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    module VARCHAR(100),
    reference_id INT,
    reference_table VARCHAR(100),
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    sent_via_email BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_recipient (recipient_id, is_read),
    INDEX idx_type (notification_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('insert', 'update', 'delete') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (changed_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_action (action),
    INDEX idx_changed (changed_at),
    INDEX idx_user (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    employee_id INT NOT NULL,
    session_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee (employee_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Configuration
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value JSON NOT NULL,
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    INDEX idx_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
