-- Optional write-in for an area not covered by the interest choices.
ALTER TABLE registrations
    ADD COLUMN interest_other VARCHAR(160) NULL AFTER interests;
