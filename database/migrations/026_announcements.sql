-- Messages to registrants, sent now or held until send_at. bin/send-due.php sends what is due.
CREATE TABLE IF NOT EXISTS announcements (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    audience     VARCHAR(20)  NOT NULL,
    subject      VARCHAR(200) NOT NULL,
    body         TEXT         NOT NULL,
    by_email     TINYINT(1)   NOT NULL DEFAULT 1,
    by_kingschat TINYINT(1)   NOT NULL DEFAULT 1,
    send_at      DATETIME     NOT NULL,
    sent_at      DATETIME     NULL,
    result       VARCHAR(255) NULL,
    created_by   VARCHAR(190) NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_due (sent_at, send_at)
);
