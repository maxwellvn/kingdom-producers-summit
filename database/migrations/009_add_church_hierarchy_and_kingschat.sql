-- Store the API-backed Group and Church selections separately while retaining
-- the legacy church_group field for compatibility with existing exports.
ALTER TABLE registrations
    ADD COLUMN kingschat_username VARCHAR(80) NULL AFTER phone,
    ADD COLUMN group_name VARCHAR(120) NULL AFTER zone,
    ADD COLUMN church_name VARCHAR(160) NULL AFTER group_name;
