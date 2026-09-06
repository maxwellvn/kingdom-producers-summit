ALTER TABLE registrations
    ADD COLUMN payment_status ENUM('unpaid','paid','not_required') NOT NULL DEFAULT 'not_required' AFTER status,
    ADD COLUMN payment_amount INT UNSIGNED NULL AFTER payment_status,
    ADD COLUMN stripe_session_id VARCHAR(255) NULL AFTER payment_amount,
    ADD KEY idx_registrations_payment (payment_status);

-- Grandfather free onsite registrations taken before paid ticketing.
UPDATE registrations SET payment_status = 'not_required' WHERE participation = 'onsite';
