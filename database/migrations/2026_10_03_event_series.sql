-- Documentação da migração. A aplicação executa estas operações de forma
-- idempotente em Database::ensureSchema(), incluindo o backfill dos slugs e a
-- transição conservadora do Lustre Comedy Club.
CREATE TABLE IF NOT EXISTS event_series (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  location TEXT DEFAULT NULL,
  description TEXT DEFAULT NULL,
  cover_image_url TEXT DEFAULT NULL,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_event_series_slug ON event_series(slug);
