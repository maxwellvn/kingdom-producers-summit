-- Page views, recorded server-side. No raw IP is stored: the visitor is a hash
-- of address, user agent and a salt that rotates daily, so the same person is
-- countable within a day but not identifiable or trackable across days.
CREATE TABLE IF NOT EXISTS page_views (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_hash CHAR(32)     NOT NULL,
    session_hash CHAR(32)     NOT NULL,
    path         VARCHAR(190) NOT NULL,
    referrer     VARCHAR(190) NULL,
    device       ENUM('desktop','mobile','tablet','bot') NOT NULL DEFAULT 'desktop',
    country      VARCHAR(2)   NULL,
    viewed_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_views_time (viewed_at),
    KEY idx_views_visitor (visitor_hash, viewed_at),
    KEY idx_views_path (path, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who is on the site or in the stream at this moment. One row per session,
-- refreshed by a heartbeat, so "connected now" is a simple recency query.
CREATE TABLE IF NOT EXISTS presence (
    session_hash CHAR(32)     NOT NULL,
    visitor_hash CHAR(32)     NOT NULL,
    path         VARCHAR(190) NOT NULL,
    context      ENUM('site','watch') NOT NULL DEFAULT 'site',
    reference    CHAR(12)     NULL,
    device       ENUM('desktop','mobile','tablet','bot') NOT NULL DEFAULT 'desktop',
    started_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_hash),
    KEY idx_presence_seen (last_seen_at),
    KEY idx_presence_context (context, last_seen_at),
    KEY idx_presence_reference (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
