-- The Commitment card filled at the end of the session, prayed over afterwards.
CREATE TABLE IF NOT EXISTS commitments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(160) NOT NULL,
    email       VARCHAR(190) NULL,
    kingschat   VARCHAR(80)  NULL,
    produce     TINYINT(1)   NOT NULL DEFAULT 1,
    records     TINYINT(1)   NOT NULL DEFAULT 1,
    buy         TINYINT(1)   NOT NULL DEFAULT 1,
    teach       TINYINT(1)   NOT NULL DEFAULT 1,
    what        VARCHAR(255) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_commitments_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
