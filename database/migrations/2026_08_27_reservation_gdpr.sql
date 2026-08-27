ALTER TABLE event_reservations ADD COLUMN gdpr_consent INTEGER NOT NULL DEFAULT 0;
ALTER TABLE event_reservations ADD COLUMN gdpr_consent_at TEXT DEFAULT NULL;
ALTER TABLE event_reservations ADD COLUMN gdpr_consent_text TEXT DEFAULT NULL;
