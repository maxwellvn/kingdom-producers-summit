CREATE TABLE IF NOT EXISTS cookie_consents (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    action         ENUM('accept_all','essential_only','custom') NOT NULL,
    preferences    TINYINT(1) NOT NULL DEFAULT 0,
    analytics      TINYINT(1) NOT NULL DEFAULT 0,
    marketing      TINYINT(1) NOT NULL DEFAULT 0,
    policy_version VARCHAR(16) NOT NULL DEFAULT '1.0',
    ip_hash        CHAR(64) NULL,
    user_agent     VARCHAR(255) NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_consents_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
