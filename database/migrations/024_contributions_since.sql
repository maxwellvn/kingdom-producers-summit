-- The moment attendance became free. Money recorded against registrations
-- made before this is a payment under the old pricing; after it, a contribution.
INSERT IGNORE INTO settings (`key`, value) VALUES ('contributions_since', NOW());
