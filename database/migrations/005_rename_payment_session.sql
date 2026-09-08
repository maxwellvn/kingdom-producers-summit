-- Neutral column name now that PayPal hosts checkout (was stripe_session_id).
-- CHANGE (not RENAME COLUMN) so it also applies on MariaDB < 10.5.
ALTER TABLE registrations
    CHANGE stripe_session_id payment_session_id VARCHAR(255) NULL AFTER payment_amount;
