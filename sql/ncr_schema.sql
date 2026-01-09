-- NCR (Non-Conformance Report) & SOP Schema

CREATE TABLE IF NOT EXISTS ncrs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ncr_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    reported_by INT NOT NULL,
    department_id INT DEFAULT NULL,
    severity VARCHAR(50) DEFAULT 'medium', -- low, medium, high
    status VARCHAR(50) DEFAULT 'draft', -- draft, submitted, under_review, approved, rejected, closed
    related_job_id INT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    review_notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_reported_by (reported_by),
    INDEX idx_department (department_id),
    INDEX idx_ncr_number (ncr_number),
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (related_job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ncr_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ncr_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    description TEXT DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ncr_id (ncr_id),
    FOREIGN KEY (ncr_id) REFERENCES ncrs(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
