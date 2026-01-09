-- Finance & BOM Schema
-- Bill of Materials, Cost Roll-up, and Financial Tracking

-- BOMs table (Bill of Materials header)
CREATE TABLE IF NOT EXISTS boms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    status ENUM('draft', 'active', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_version (product_id, version),
    INDEX idx_product_status (product_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOM Items (materials/components in a BOM)
CREATE TABLE IF NOT EXISTS bom_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bom_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit_cost DECIMAL(10,2) DEFAULT NULL,
    sequence INT DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bom_id) REFERENCES boms(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_bom (bom_id),
    INDEX idx_material (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Costing (cost tracking per job)
CREATE TABLE IF NOT EXISTS job_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    cost_type ENUM('materials', 'labor', 'overhead', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT DEFAULT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    INDEX idx_job_type (job_id, cost_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accounting Exports (log of exported data for accounting systems)
CREATE TABLE IF NOT EXISTS accounting_exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    export_type VARCHAR(50) NOT NULL,
    job_id INT DEFAULT NULL,
    export_data JSON NOT NULL,
    exported_by INT NOT NULL,
    exported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    FOREIGN KEY (exported_by) REFERENCES users(id),
    INDEX idx_export_type (export_type),
    INDEX idx_job (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add related_job_id to maintenance_tasks if not exists (for labor costing)
ALTER TABLE maintenance_tasks ADD COLUMN IF NOT EXISTS related_job_id INT DEFAULT NULL;
ALTER TABLE maintenance_tasks ADD FOREIGN KEY IF NOT EXISTS (related_job_id) REFERENCES jobs(id) ON DELETE SET NULL;
