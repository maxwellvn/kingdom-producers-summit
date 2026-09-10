-- Registrations an organiser issued by hand, and who issued them.
ALTER TABLE registrations
    ADD COLUMN issued_by VARCHAR(190) NULL AFTER user_agent;
