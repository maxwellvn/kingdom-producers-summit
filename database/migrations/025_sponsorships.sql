-- Gifts from the /sponsor page: anyone, any amount, no registration needed.
CREATE TABLE IF NOT EXISTS sponsorships (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    amount_pence  INT UNSIGNED NOT NULL,
    method        ENUM('stripe','espees','revolut') NOT NULL,
    -- pending: chose a method, nothing seen yet. claimed: says they sent it. paid: confirmed.
    status        ENUM('pending','claimed','paid') NOT NULL DEFAULT 'pending',
    session_id    VARCHAR(120) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at       DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_email (email)
);
