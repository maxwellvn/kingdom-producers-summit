-- When the "your place is not held until you pay" reminder went out, so it
-- is sent once, and when an organiser last re-sent the payment email.
ALTER TABLE registrations
    ADD COLUMN payment_reminder_sent_at DATETIME NULL AFTER payment_session_id,
    ADD COLUMN payment_email_resent_at  DATETIME NULL AFTER payment_reminder_sent_at,
    ADD KEY idx_registrations_reminder (payment_status, status, payment_reminder_sent_at, created_at);
