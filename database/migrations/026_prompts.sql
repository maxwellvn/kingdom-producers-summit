-- Polls and questions the organisers put to viewers during the stream.
CREATE TABLE IF NOT EXISTS prompts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind       ENUM('poll','question') NOT NULL,
    question   VARCHAR(255) NOT NULL,
    options    JSON NULL,
    status     ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_prompts_status (status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prompt_answers (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    prompt_id  BIGINT UNSIGNED NOT NULL,
    reference  CHAR(12) NOT NULL,
    author     VARCHAR(120) NOT NULL,
    choice     TINYINT UNSIGNED NULL,
    text       VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prompt_answer (prompt_id, reference),
    CONSTRAINT fk_prompt_answers_prompt FOREIGN KEY (prompt_id) REFERENCES prompts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
