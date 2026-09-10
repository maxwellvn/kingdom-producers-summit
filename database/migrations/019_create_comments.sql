-- Comments left during the event. Off unless an organiser switches them on,
-- and clearable from the admin dashboard.
CREATE TABLE IF NOT EXISTS comments (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference   CHAR(12)     NOT NULL,
    author_name VARCHAR(120) NOT NULL,
    body        VARCHAR(500) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_created (created_at),
    KEY idx_comments_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
