-- Executed automatically by Database::ensureSchema; documented here for manual deployments.
CREATE TABLE IF NOT EXISTS event_series (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE,
  location TEXT DEFAULT NULL, description TEXT DEFAULT NULL, cover_image_url TEXT DEFAULT NULL,
  is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
-- SQLite has no portable ADD COLUMN IF NOT EXISTS. The application checks PRAGMA
-- table_info(events) before adding events.series_id and events.slug.
