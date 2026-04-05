PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS event_comedians;
DROP TABLE IF EXISTS event_schedule_items;
DROP TABLE IF EXISTS event_reservations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS comedians;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS public_pages;
DROP TABLE IF EXISTS newsletter_subscriptions;
DROP TABLE IF EXISTS site_settings;

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
  city TEXT DEFAULT NULL,
  instagram TEXT DEFAULT NULL,
  price_bar NUMERIC DEFAULT 0,
  price_auditorium NUMERIC DEFAULT 0,
  bio TEXT DEFAULT NULL,
  attachment_path TEXT DEFAULT NULL,
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
  reservations_open INTEGER NOT NULL DEFAULT 1,
  reservation_capacity INTEGER NOT NULL DEFAULT 0,
  cachet_total NUMERIC DEFAULT 0,
  artist_map_link TEXT DEFAULT NULL,
  artist_details TEXT DEFAULT NULL,
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


CREATE TABLE event_reservations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  customer_name TEXT NOT NULL,
  customer_email TEXT NOT NULL,
  customer_phone TEXT DEFAULT NULL,
  tickets INTEGER NOT NULL DEFAULT 1,
  notes TEXT DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'confirmed', 'cancelled')),
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE event_schedule_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  starts_at TEXT NOT NULL,
  duration_minutes INTEGER NOT NULL DEFAULT 15,
  item_type TEXT NOT NULL DEFAULT 'artist' CHECK (item_type IN ('artist', 'break', 'technical', 'doors', 'other')),
  title TEXT NOT NULL,
  responsible TEXT DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);


CREATE TABLE public_pages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  excerpt TEXT DEFAULT NULL,
  content TEXT DEFAULT NULL,
  hero_image_url TEXT DEFAULT NULL,
  display_mode TEXT NOT NULL DEFAULT 'section' CHECK (display_mode IN ('section', 'page')),
  section_type TEXT NOT NULL DEFAULT 'default' CHECK (section_type IN ('default', 'about', 'services', 'contact_form')),
  section_style TEXT NOT NULL DEFAULT 'card' CHECK (section_style IN ('card', 'split', 'icons', 'highlight')),
  section_config_json TEXT DEFAULT NULL,
  is_published INTEGER NOT NULL DEFAULT 1,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_subscriptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  name TEXT DEFAULT NULL,
  gdpr_consent INTEGER NOT NULL DEFAULT 0,
  consent_text TEXT NOT NULL,
  source TEXT DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'unsubscribed')),
  subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP,
  unsubscribed_at TEXT DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE site_settings (
  setting_key TEXT PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@standup.local', '$2y$12$sfHHEGGOErVolRpcZ83vDe/JN3e4dQ6I7MQe1x2VV40K50K.bmf.e', 'admin'),
('Ana Ribeiro', 'ana@standup.local', '$2y$12$jKmXi3xg8.OTAFanG3O5R.6Ow6KWyoBS0oq/UxQ0sMA4fuPGXYTty', 'comedian'),
('Bruno Silva', 'bruno@standup.local', '$2y$12$jKmXi3xg8.OTAFanG3O5R.6Ow6KWyoBS0oq/UxQ0sMA4fuPGXYTty', 'comedian');

INSERT INTO comedians (user_id, name, stage_name, email, phone, city, instagram, price_bar, price_auditorium, bio, notes) VALUES
(2, 'Ana Ribeiro', 'Ana Riso', 'ana@standup.local', '910000001', 'Lisboa', '@anariso', 300, 700, 'Comediante de observação com forte interação com público.', 'Especialista em crowd work.'),
(3, 'Bruno Silva', 'Bruno Punch', 'bruno@standup.local', '910000002', 'Porto', '@brunopunch', 400, 900, 'Headliner com humor de storytelling e improviso.', 'Headliner frequente em Lisboa.');

INSERT INTO clients (name, contact_person, phone, email, address, notes) VALUES
('Bar Gargalhada', 'Marta Costa', '210000001', 'marta@gargalhada.pt', 'Rua da Alegria 10, Lisboa', 'Eventos mensais'),
('Restaurante Comédia & Cia', 'João Pinto', '220000002', 'joao@comediaecia.pt', 'Av. Central 55, Porto', 'Palco equipado com som e luz.');

INSERT INTO events (title, date, time, location, client_id, cachet_total, artist_map_link, artist_details, notes) VALUES
('Noite de Stand-Up Lisboa', '2026-04-10', '21:30:00', 'Bar Gargalhada', 1, 550.00, 'https://maps.google.com/?q=Rua+da+Alegria+10,+Lisboa', 'Chegada às 20:15 para soundcheck. Entrada técnica pela porta lateral.', 'Entrada livre até às 22h.'),
('Comedy Dinner Porto', '2026-04-17', '22:00:00', 'Comédia & Cia', 2, 700.00, 'https://maps.google.com/?q=Av.+Central+55,+Porto', 'Parque disponível no piso -1. Contacto técnico no local: João.', 'Evento com jantar incluído.');

INSERT INTO event_comedians (event_id, comedian_id, role, cachet, notes) VALUES
(1, 1, 'host', 200.00, 'Aquecimento e interação.'),
(1, 2, 'headliner', 350.00, 'Set principal de 40 minutos.'),
(2, 1, 'opener', 250.00, 'Abertura de 20 minutos.'),
(2, 2, 'headliner', 450.00, 'Headliner com 50 minutos.');

INSERT INTO event_schedule_items (event_id, starts_at, duration_minutes, item_type, title, responsible, notes, sort_order) VALUES
(1, '20:30', 30, 'doors', 'Abertura de portas', 'Produção', 'Check-in de convidados e confirmação técnica.', 1),
(1, '21:00', 15, 'artist', 'Boas-vindas e aquecimento', 'Ana Riso', 'Introdução ao formato da noite.', 2),
(1, '21:15', 40, 'artist', 'Set principal', 'Bruno Punch', 'Material principal + crowd work.', 3),
(1, '21:55', 10, 'break', 'Pausa técnica', 'Técnico de som', 'Ajuste de microfones e luz.', 4),
(1, '22:05', 20, 'artist', 'Encerramento e fotos', 'Ana Riso', 'Agradecimentos e conteúdo para redes.', 5);


INSERT INTO public_pages (title, slug, excerpt, content, hero_image_url, display_mode, section_type, section_style, section_config_json, is_published, sort_order) VALUES
('Sobre nós', 'sobre-nos', 'Conhece a nossa missão e equipa de produção.', '<p>Somos uma produtora artística focada em experiências ao vivo, booking de talentos e curadoria de eventos de comédia.</p><p>Trabalhamos com marcas, espaços culturais e artistas para criar noites memoráveis.</p>', 'https://images.unsplash.com/photo-1497032628192-86f99bcd76bc?auto=format&fit=crop&w=1400&q=80', 'section', 'about', 'split', '{"cta_text":"Levamos comédia para marcas, teatros e eventos privados em todo o país.","cta_button_text":"Falar com equipa","contact_email_to":"","contact_fields":["name","email","message"],"services":[]}', 1, 10),
('Serviços', 'servicos', 'Produção, agenciamento e consultoria para espetáculos.', '<p>Do conceito à execução, desenhamos noites com público esgotado e impacto real.</p>', 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=1400&q=80', 'section', 'services', 'icons', '{"cta_text":"Escolhe um serviço e pede proposta personalizada.","cta_button_text":"Pedir proposta","contact_email_to":"","contact_fields":["name","email","message"],"services":[{"name":"Produção de Evento","icon":"calendar-event","description":"Planeamento, operação e direção de palco."},{"name":"Booking de Comediantes","icon":"mic-fill","description":"Seleção de artistas para cada tipo de público."},{"name":"Consultoria Criativa","icon":"lightbulb","description":"Formatos de conteúdo e ativações de marca."},{"name":"Apresentação / Host","icon":"emoji-laughing","description":"Mestre de cerimónias com energia de stand-up."}]}', 1, 20),
('Contactos', 'contactos', 'Fala connosco para reservas e propostas comerciais.', '<p>Estamos disponíveis para parcerias em todo o país.</p>', 'https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=1400&q=80', 'section', 'contact_form', 'highlight', '{"cta_text":"Conta-nos o teu objetivo e criamos uma proposta com timing, elenco e produção.","cta_button_text":"Enviar mensagem","contact_email_to":"booking@casadeartistas.pt","contact_fields":["name","email","phone","subject","message"],"services":[]}', 1, 30);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('home_tagline', 'Produção • Booking • Experiências'),
('home_title', 'Humor e espetáculos com um palco inesquecível.'),
('home_description', 'Layout inspirado no visual Big Picture com imagem de fundo marcante e conteúdo em cartões translúcidos.'),
('newsletter_consent_text', 'Autorizo o tratamento dos meus dados para receber comunicações de eventos e novidades, de acordo com o RGPD.'),
('reservation_email_template_a', 'Olá {customer_name},

Recebemos a tua reserva para "{event_title}" no dia {event_date} às {event_time}.
Bilhetes reservados: {tickets}.

Obrigado!'),
('reservation_email_template_b', 'Olá {customer_name},

A tua reserva para "{event_title}" foi submetida com sucesso.
Data: {event_date} às {event_time}
Nº de bilhetes: {tickets}

Entraremos em contacto em breve para confirmação final.'),
('reservation_email_template_selected', 'a');

PRAGMA foreign_keys = ON;
