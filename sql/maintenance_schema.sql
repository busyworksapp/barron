-- Maintenance Module Schema

CREATE TABLE IF NOT EXISTS machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT DEFAULT NULL,
    location VARCHAR(255),
    purchase_date DATE DEFAULT NULL,
    warranty_expiry DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active', -- active, inactive, retired
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_department (department_id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_number VARCHAR(50) UNIQUE NOT NULL,
    machine_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50) DEFAULT 'preventive', -- preventive, corrective, inspection
    priority VARCHAR(50) DEFAULT 'medium', -- low, medium, high
    scheduled_date DATE NOT NULL,
    estimated_hours DECIMAL(5,2) DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'scheduled', -- scheduled, in_progress, completed, overdue, cancelled
    completed_date DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX idx_task_number (task_number),
    INDEX idx_machine (machine_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date),
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    notes TEXT NOT NULL,
    hours_spent DECIMAL(5,2) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task_id (task_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (task_id) REFERENCES maintenance_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
