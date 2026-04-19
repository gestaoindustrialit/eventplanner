CREATE TABLE IF NOT EXISTS checklist_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS checklist_template_fields (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  template_id INTEGER NOT NULL,
  label TEXT NOT NULL,
  field_type TEXT NOT NULL DEFAULT 'checkbox' CHECK (field_type IN ('checkbox', 'text')),
  is_required INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS event_checklists (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL UNIQUE,
  template_id INTEGER DEFAULT NULL,
  name TEXT NOT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS event_checklist_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_checklist_id INTEGER NOT NULL,
  label TEXT NOT NULL,
  field_type TEXT NOT NULL DEFAULT 'checkbox' CHECK (field_type IN ('checkbox', 'text')),
  is_required INTEGER NOT NULL DEFAULT 0,
  value TEXT DEFAULT NULL,
  is_checked INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_checklist_id) REFERENCES event_checklists(id) ON DELETE CASCADE
);
