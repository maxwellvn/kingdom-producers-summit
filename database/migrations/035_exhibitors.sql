-- Applications to exhibit at an event. The team approves or declines each one; the applicant is emailed either way.
CREATE TABLE IF NOT EXISTS exhibitors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event VARCHAR(20) NOT NULL DEFAULT 'manchester',
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    kingschat VARCHAR(80) NULL,
    business VARCHAR(160) NOT NULL,
    category VARCHAR(60) NOT NULL,
    description TEXT NOT NULL,
    website VARCHAR(255) NULL,
    staff TINYINT UNSIGNED NOT NULL DEFAULT 1,
    needs_power TINYINT(1) NOT NULL DEFAULT 0,
    notes VARCHAR(600) NULL,
    status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    decided_by VARCHAR(190) NULL,
    decided_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_exhibitors_event_email (event, email),
    KEY idx_exhibitors_event_status (event, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
