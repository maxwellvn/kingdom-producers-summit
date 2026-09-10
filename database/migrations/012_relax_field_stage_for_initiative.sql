-- Initiative registrations collect personal details only, so field and stage are optional.
ALTER TABLE registrations
    MODIFY COLUMN field VARCHAR(80) NULL,
    MODIFY COLUMN producer_stage ENUM('emerge','build','establish','multiply') NULL;
