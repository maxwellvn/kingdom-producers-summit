-- Offline payment methods (Espees / bank transfer) introduce an intermediate
-- 'claimed' state: the registrant says they have paid, an admin confirms receipt.
ALTER TABLE registrations
    MODIFY payment_status ENUM('unpaid','paid','claimed','not_required') NOT NULL DEFAULT 'not_required',
    ADD COLUMN payment_method VARCHAR(20) NULL AFTER payment_status;
