-- =====================================================
-- Add Production Stages Table
-- This table stores dynamic production stages per department
-- =====================================================

CREATE TABLE IF NOT EXISTS production_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    stage_code VARCHAR(50) NOT NULL,
    stage_order INT NOT NULL DEFAULT 1,
    description TEXT,
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_dept_stage_code (department_id, stage_code),
    INDEX idx_department (department_id),
    INDEX idx_stage_order (stage_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample production stages for different departments
-- Uncomment for sample data:
/*
INSERT INTO production_stages (department_id, stage_name, stage_code, stage_order, is_active, created_by) VALUES
-- Embroidery Department
(1, 'Design Setup', 'EMB_DESIGN', 1, 1, 1),
(1, 'Hooping', 'EMB_HOOP', 2, 1, 1),
(1, 'Embroidery', 'EMB_STITCH', 3, 1, 1),
(1, 'Quality Check', 'EMB_QC', 4, 1, 1),

-- Screen Printing Department
(2, 'Screen Preparation', 'SCR_PREP', 1, 1, 1),
(2, 'Printing', 'SCR_PRINT', 2, 1, 1),
(2, 'Drying', 'SCR_DRY', 3, 1, 1),
(2, 'Quality Check', 'SCR_QC', 4, 1, 1);
*/

SELECT 'Production stages table created successfully!' AS Status;
