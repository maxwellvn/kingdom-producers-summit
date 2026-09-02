CREATE TABLE IF NOT EXISTS attendances (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    registration_id  INT UNSIGNED NOT NULL,
    checked_in_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    checked_in_by    VARCHAR(190) NOT NULL,
    scan_method      ENUM('qr','manual') NOT NULL DEFAULT 'qr',
    ip_address       VARBINARY(16) NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_attendances_registration (registration_id),
    KEY idx_attendances_checked_in (checked_in_at),
    CONSTRAINT fk_attendances_registration
        FOREIGN KEY (registration_id) REFERENCES registrations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
