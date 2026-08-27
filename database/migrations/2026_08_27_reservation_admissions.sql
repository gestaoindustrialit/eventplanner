ALTER TABLE event_reservations
ADD COLUMN admission_status TEXT NOT NULL DEFAULT 'pending';
