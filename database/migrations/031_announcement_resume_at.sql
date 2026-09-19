-- When the mail host throttles, the announcement waits until this time before the runner tries again.
ALTER TABLE announcements ADD COLUMN resume_at DATETIME NULL AFTER result;
