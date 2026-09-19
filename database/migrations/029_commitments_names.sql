-- Title, first and last name instead of one name field; the "what" line is gone.
ALTER TABLE commitments
    ADD COLUMN title VARCHAR(20) NULL AFTER id,
    ADD COLUMN first_name VARCHAR(80) NOT NULL DEFAULT '' AFTER title,
    ADD COLUMN last_name VARCHAR(80) NOT NULL DEFAULT '' AFTER first_name;
UPDATE commitments SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1)) WHERE first_name = '';
ALTER TABLE commitments DROP COLUMN name, DROP COLUMN what;
