-- Match the registrations table so the join in the runner works on MySQL 8 (default is 0900_ai_ci).
ALTER TABLE announcement_deliveries CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
