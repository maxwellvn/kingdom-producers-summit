-- What is happening around the stream, as it happens, for the admin log.
CREATE TABLE IF NOT EXISTS stream_events (
    id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    at      DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    kind    VARCHAR(24)  NOT NULL,
    detail  VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_stream_events_at (at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
