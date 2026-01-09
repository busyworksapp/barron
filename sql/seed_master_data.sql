-- Master Data Seed SQL
-- This script inserts minimal sample data for products, departments, users (including an admin), and production stages.
-- Run this after your main schema has been applied.

-- Departments (sample)
INSERT INTO departments (code, name, description) VALUES
('CUTTING', 'Cutting', 'Raw material cutting and preparation'),
('WELDING', 'Welding', 'Metal fabrication and welding'),
('ASSEMBLY', 'Assembly', 'Component assembly'),
('FINISHING', 'Finishing', 'Paint, polish, and final finishing'),
('QC', 'Quality Control', 'Inspection and quality assurance')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Products (sample)
INSERT INTO products (sku, name, description, unit) VALUES
('P-001', 'Widget A', 'Standard widget model A', 'pcs'),
('P-002', 'Widget B', 'Premium widget model B', 'pcs'),
('P-003', 'Bracket X', 'Steel bracket X-type', 'pcs'),
('P-004', 'Assembly Kit', 'Complete assembly kit with hardware', 'kit'),
('P-005', 'Custom Panel', 'Customizable panel (made to order)', 'panel')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Users (sample: 1 admin, 1 planner, 1 manager, 1 operator)
-- Password for all: 'password' (hashed via PHP password_hash with PASSWORD_DEFAULT)
-- Replace these hashes with fresh ones in production or use a proper seeding script that invokes password_hash()
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@barron.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin'),
('planner1', 'planner@barron.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Planner One', 'planner'),
('manager1', 'manager@barron.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager One', 'manager'),
('operator1', 'operator@barron.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator One', 'operator')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- Production Stages (sample linked to departments)
-- Fetch dept IDs dynamically, or hard-code known IDs after departments are seeded
SET @cutting_dept = (SELECT id FROM departments WHERE code = 'CUTTING' LIMIT 1);
SET @welding_dept = (SELECT id FROM departments WHERE code = 'WELDING' LIMIT 1);
SET @assembly_dept = (SELECT id FROM departments WHERE code = 'ASSEMBLY' LIMIT 1);
SET @finishing_dept = (SELECT id FROM departments WHERE code = 'FINISHING' LIMIT 1);
SET @qc_dept = (SELECT id FROM departments WHERE code = 'QC' LIMIT 1);

INSERT INTO production_stages (name, description, stage_order, department_id) VALUES
('Cut to Size', 'Cut raw materials to specifications', 10, @cutting_dept),
('Weld Joints', 'Weld primary joints', 20, @welding_dept),
('Sub-Assembly', 'Assemble sub-components', 30, @assembly_dept),
('Final Assembly', 'Final assembly of all parts', 40, @assembly_dept),
('Paint & Finish', 'Apply paint and finish treatments', 50, @finishing_dept),
('QC Inspection', 'Quality control final inspection', 60, @qc_dept)
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Done
SELECT 'Master data seeded successfully!' AS message;
