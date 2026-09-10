-- One live viewer per registration. A new sign-in replaces the pass, and the
-- previous device learns it has been taken over on its next heartbeat.
CREATE TABLE IF NOT EXISTS watch_passes (
    reference    CHAR(12)     NOT NULL,
    session_hash CHAR(32)     NOT NULL,
    device       VARCHAR(20)  NOT NULL DEFAULT 'desktop',
    ip_hash      CHAR(32)     NULL,
    issued_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    takeovers    INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (reference),
    KEY idx_watch_session (session_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
