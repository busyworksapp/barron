-- Finance/BOM Module Database Schema
-- Bill of Materials tables for product component tracking and cost analysis

-- BOM Master Table
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
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_bom_number (bom_number),
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOM Components Table
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

-- Add BOM permissions to roles (if not exists)
-- INSERT INTO permissions (name, description) VALUES 
-- ('finance.view_bom', 'View Bill of Materials'),
-- ('finance.edit_bom', 'Create and edit Bill of Materials')
-- ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Sample BOM Data (Optional - for testing)
/*
INSERT INTO bom (bom_number, product_id, version, status, description, overhead_percentage, total_cost, created_by) VALUES
('BOM202601001', 1, '1.0', 'active', 'Standard production BOM', 10.00, 250.00, 1);

INSERT INTO bom_components (bom_id, component_name, quantity, unit, unit_cost, total_cost) VALUES
(1, 'Steel Sheet 2mm', 5.00, 'kg', 25.00, 125.00),
(1, 'Fasteners M6', 20.00, 'pcs', 2.50, 50.00),
(1, 'Paint - Black', 0.50, 'l', 80.00, 40.00);
*/
