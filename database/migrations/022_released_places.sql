-- When an unpaid place was released back to the pool, so the person can be
-- told and, if places remain, register again.
ALTER TABLE registrations
    ADD COLUMN released_at DATETIME NULL AFTER payment_email_resent_at;
