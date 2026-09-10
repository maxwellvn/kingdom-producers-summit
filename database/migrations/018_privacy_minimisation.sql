-- Data minimisation: a registration does not need the visitor's address or
-- browser string, so the stored ones are cleared and the columns are dropped.
ALTER TABLE registrations
    DROP COLUMN ip_address,
    DROP COLUMN user_agent;

-- Retention: cookie consent records only need the choice and when it was made.
ALTER TABLE cookie_consents
    DROP COLUMN user_agent;
