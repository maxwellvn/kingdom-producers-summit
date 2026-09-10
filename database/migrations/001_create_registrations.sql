CREATE TABLE IF NOT EXISTS registrations (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference           CHAR(12)     NOT NULL,

    -- Path: onsite = attending in Essex; online = access to some livestream sessions;
    -- initiative = not attending, joining the Kingdom Producers initiative & portal
    participation       ENUM('onsite','online','initiative') NOT NULL,

    title               VARCHAR(20)  NULL,
    first_name          VARCHAR(80)  NOT NULL,
    last_name           VARCHAR(80)  NOT NULL,
    email               VARCHAR(190) NOT NULL,
    phone               VARCHAR(30)  NULL,
    country             VARCHAR(80)  NOT NULL,
    city                VARCHAR(80)  NULL,

    age_band            ENUM('under18','18-24','25-34','35-44','45-54','55-64','65plus') NULL,
    church_group        VARCHAR(120) NULL,
    zone                VARCHAR(120) NULL,
    organisation        VARCHAR(120) NULL,
    role_title          VARCHAR(120) NULL,

    field               VARCHAR(80)  NOT NULL,
    producer_stage      ENUM('emerge','build','establish','multiply') NOT NULL,
    what_to_produce     TEXT         NULL,

    interests           JSON         NULL,
    hear_about          VARCHAR(60)  NULL,

    -- onsite specifics
    onsite_days         JSON         NULL,
    dietary             VARCHAR(160) NULL,
    accessibility       VARCHAR(255) NULL,
    needs_letter        TINYINT(1)   NOT NULL DEFAULT 0,
    emergency_contact   VARCHAR(160) NULL,

    -- online / initiative specifics
    wants_updates       TINYINT(1)   NOT NULL DEFAULT 1,
    wants_portal        TINYINT(1)   NOT NULL DEFAULT 0,
    contribute_as       JSON         NULL,
    portal_interest     TEXT         NULL,

    consent_terms       TINYINT(1)   NOT NULL DEFAULT 0,
    consent_marketing   TINYINT(1)   NOT NULL DEFAULT 0,

    ip_address          VARBINARY(16) NULL,
    user_agent          VARCHAR(255) NULL,
    status              ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'confirmed',

    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_registrations_reference (reference),
    UNIQUE KEY uq_registrations_email (email),
    KEY idx_registrations_participation (participation),
    KEY idx_registrations_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
