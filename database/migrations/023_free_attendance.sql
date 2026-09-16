-- Attending is free. Anyone still waiting to pay from before is confirmed,
-- and the chase bookkeeping goes.
UPDATE registrations
   SET status = 'confirmed', payment_status = 'not_required', payment_amount = NULL
 WHERE status = 'pending' AND payment_status = 'unpaid';

ALTER TABLE registrations
    DROP INDEX idx_registrations_reminder,
    DROP COLUMN payment_reminder_sent_at,
    DROP COLUMN payment_email_resent_at,
    DROP COLUMN released_at;
