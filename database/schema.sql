PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS event_comedians;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS comedians;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'comedian' CHECK (role IN ('admin', 'comedian')),
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE comedians (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  name TEXT NOT NULL,
  stage_name TEXT DEFAULT NULL,
  email TEXT DEFAULT NULL,
  phone TEXT DEFAULT NULL,
  instagram TEXT DEFAULT NULL,
  notes TEXT,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE clients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  contact_person TEXT DEFAULT NULL,
  phone TEXT DEFAULT NULL,
  email TEXT DEFAULT NULL,
  address TEXT DEFAULT NULL,
  notes TEXT,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  date TEXT NOT NULL,
  time TEXT NOT NULL,
  location TEXT NOT NULL,
  client_id INTEGER NOT NULL,
  cachet_total NUMERIC DEFAULT 0,
  notes TEXT,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
);

CREATE TABLE event_comedians (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  comedian_id INTEGER NOT NULL,
  role TEXT NOT NULL DEFAULT 'opener' CHECK (role IN ('host', 'opener', 'headliner')),
  cachet NUMERIC DEFAULT 0,
  notes TEXT,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (comedian_id) REFERENCES comedians(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@standup.local', '$2y$12$sfHHEGGOErVolRpcZ83vDe/JN3e4dQ6I7MQe1x2VV40K50K.bmf.e', 'admin'),
('Ana Ribeiro', 'ana@standup.local', '$2y$12$jKmXi3xg8.OTAFanG3O5R.6Ow6KWyoBS0oq/UxQ0sMA4fuPGXYTty', 'comedian'),
('Bruno Silva', 'bruno@standup.local', '$2y$12$jKmXi3xg8.OTAFanG3O5R.6Ow6KWyoBS0oq/UxQ0sMA4fuPGXYTty', 'comedian');

INSERT INTO comedians (user_id, name, stage_name, email, phone, instagram, notes) VALUES
(2, 'Ana Ribeiro', 'Ana Riso', 'ana@standup.local', '910000001', '@anariso', 'Especialista em crowd work.'),
(3, 'Bruno Silva', 'Bruno Punch', 'bruno@standup.local', '910000002', '@brunopunch', 'Headliner frequente em Lisboa.');

INSERT INTO clients (name, contact_person, phone, email, address, notes) VALUES
('Bar Gargalhada', 'Marta Costa', '210000001', 'marta@gargalhada.pt', 'Rua da Alegria 10, Lisboa', 'Eventos mensais'),
('Restaurante Comédia & Cia', 'João Pinto', '220000002', 'joao@comediaecia.pt', 'Av. Central 55, Porto', 'Palco equipado com som e luz.');

INSERT INTO events (title, date, time, location, client_id, cachet_total, notes) VALUES
('Noite de Stand-Up Lisboa', '2026-04-10', '21:30:00', 'Bar Gargalhada', 1, 550.00, 'Entrada livre até às 22h.'),
('Comedy Dinner Porto', '2026-04-17', '22:00:00', 'Comédia & Cia', 2, 700.00, 'Evento com jantar incluído.');

INSERT INTO event_comedians (event_id, comedian_id, role, cachet, notes) VALUES
(1, 1, 'host', 200.00, 'Aquecimento e interação.'),
(1, 2, 'headliner', 350.00, 'Set principal de 40 minutos.'),
(2, 1, 'opener', 250.00, 'Abertura de 20 minutos.'),
(2, 2, 'headliner', 450.00, 'Headliner com 50 minutos.');

PRAGMA foreign_keys = ON;
