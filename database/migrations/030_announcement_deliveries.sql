-- One row per person per announcement. The minute runner works through pending rows,
-- pauses when the mail host throttles, and carries on until every row is sent or given up.
CREATE TABLE IF NOT EXISTS announcement_deliveries (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT UNSIGNED NOT NULL,
    reference       CHAR(12)     NOT NULL,
    email           VARCHAR(190) NOT NULL,
    status          ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error      VARCHAR(255) NULL,
    claimed_at      DATETIME     NULL,
    sent_at         DATETIME     NULL,
    UNIQUE KEY uq_delivery (announcement_id, reference),
    KEY idx_delivery_status (announcement_id, status)
);
