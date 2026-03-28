CREATE DATABASE IF NOT EXISTS eventplanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eventplanner;

DROP TABLE IF EXISTS event_comedians;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS comedians;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'comedian') NOT NULL DEFAULT 'comedian',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE comedians (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(120) NOT NULL,
  stage_name VARCHAR(120) DEFAULT NULL,
  email VARCHAR(180) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  instagram VARCHAR(120) DEFAULT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comedians_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  contact_person VARCHAR(120) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  email VARCHAR(180) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  location VARCHAR(180) NOT NULL,
  client_id INT NOT NULL,
  cachet_total DECIMAL(10,2) DEFAULT 0,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
);

CREATE TABLE event_comedians (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  comedian_id INT NOT NULL,
  role ENUM('host', 'opener', 'headliner') NOT NULL DEFAULT 'opener',
  cachet DECIMAL(10,2) DEFAULT 0,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ec_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_ec_comedian FOREIGN KEY (comedian_id) REFERENCES comedians(id) ON DELETE CASCADE
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
