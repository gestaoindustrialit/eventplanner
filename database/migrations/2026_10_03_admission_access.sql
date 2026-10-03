ALTER TABLE events ADD COLUMN admission_group TEXT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS user_admission_access (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  event_id INTEGER DEFAULT NULL,
  event_group TEXT DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  CHECK (event_id IS NOT NULL OR event_group IS NOT NULL),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_user_admission_event ON user_admission_access(user_id, event_id) WHERE event_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS idx_user_admission_group ON user_admission_access(user_id, event_group) WHERE event_group IS NOT NULL;
