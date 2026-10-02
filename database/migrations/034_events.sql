-- Two events share one site: Manchester and Ireland, on the same day.
-- Every row that belongs to one event says which. Existing rows were all Manchester.
-- Check-ins, watch passes, poll answers and deliveries take their event from the
-- registration, poll or announcement they belong to, so they need no column of their own.

ALTER TABLE registrations
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    DROP INDEX uq_registrations_email,
    ADD UNIQUE KEY uq_registrations_event_email (event, email),
    ADD KEY idx_registrations_event_created (event, created_at);

ALTER TABLE comments
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    ADD KEY idx_comments_event_created (event, created_at);

ALTER TABLE prompts
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    ADD KEY idx_prompts_event_status (event, status);

ALTER TABLE announcements
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    ADD KEY idx_announcements_event (event, created_at);

ALTER TABLE sponsorships
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    ADD KEY idx_sponsorships_event (event, status);

ALTER TABLE commitments
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    ADD KEY idx_commitments_event (event, created_at);

ALTER TABLE stream_events
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id,
    ADD KEY idx_stream_events_event_at (event, at);

ALTER TABLE archives
    ADD COLUMN event VARCHAR(20) NOT NULL DEFAULT 'manchester' AFTER id;
