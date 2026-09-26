-- One row per archived edition. Its data lives in archive_<slug>_<table> copies of the live tables.
CREATE TABLE IF NOT EXISTS archives (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(24) NOT NULL,
    label VARCHAR(120) NOT NULL,
    counts JSON NOT NULL,
    cleared TINYINT(1) NOT NULL DEFAULT 0,
    created_by VARCHAR(190) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_archives_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room for "archive:<slug>" so a past edition can be written to.
ALTER TABLE announcements MODIFY audience VARCHAR(40) NOT NULL;
