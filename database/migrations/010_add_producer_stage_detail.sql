-- Optional context for people whose situation needs more detail than one stage.
ALTER TABLE registrations
    ADD COLUMN producer_stage_detail VARCHAR(500) NULL AFTER producer_stage;
