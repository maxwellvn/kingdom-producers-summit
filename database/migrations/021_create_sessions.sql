-- Sessions live in the database rather than on the container's disk, so a
-- redeploy does not sign everyone out or invalidate the form they have open.
CREATE TABLE IF NOT EXISTS sessions (
    id            VARCHAR(128) NOT NULL,
    data          MEDIUMBLOB   NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_sessions_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
