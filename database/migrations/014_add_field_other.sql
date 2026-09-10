-- "Other" in the field list needs somewhere to put the answer.
ALTER TABLE registrations
    ADD COLUMN field_other VARCHAR(120) NULL AFTER field;
