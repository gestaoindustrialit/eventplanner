CREATE TABLE IF NOT EXISTS crm_contacts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  entity_name TEXT NOT NULL,
  contact_name TEXT DEFAULT NULL,
  email TEXT DEFAULT NULL,
  phone TEXT DEFAULT NULL,
  website TEXT DEFAULT NULL,
  social_profile TEXT DEFAULT NULL,
  country TEXT DEFAULT NULL,
  city TEXT DEFAULT NULL,
  market TEXT NOT NULL DEFAULT 'nacional' CHECK (market IN ('nacional', 'internacional')),
  contact_type TEXT NOT NULL DEFAULT 'outro' CHECK (contact_type IN ('venda', 'apoio', 'parceria', 'patrocinador', 'media', 'fornecedor', 'outro')),
  status TEXT NOT NULL DEFAULT 'novo' CHECK (status IN ('novo', 'por_contactar', 'contactado', 'aguardar_resposta', 'em_negociacao', 'proposta_enviada', 'interessado', 'fechado_ganho', 'fechado_perdido', 'sem_interesse', 'arquivado')),
  priority TEXT NOT NULL DEFAULT 'media' CHECK (priority IN ('baixa', 'media', 'alta', 'urgente')),
  lead_source TEXT DEFAULT NULL,
  first_contact_at TEXT DEFAULT NULL,
  last_contact_at TEXT DEFAULT NULL,
  next_follow_up_at TEXT DEFAULT NULL,
  potential_value NUMERIC DEFAULT NULL,
  observations TEXT DEFAULT NULL,
  internal_notes TEXT DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS crm_contact_notes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  contact_id INTEGER NOT NULL,
  note TEXT NOT NULL,
  created_by_user_id INTEGER DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contact_id) REFERENCES crm_contacts(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_crm_contacts_status ON crm_contacts(status);
CREATE INDEX IF NOT EXISTS idx_crm_contacts_type ON crm_contacts(contact_type);
CREATE INDEX IF NOT EXISTS idx_crm_contacts_market ON crm_contacts(market);
CREATE INDEX IF NOT EXISTS idx_crm_contacts_priority ON crm_contacts(priority);
CREATE INDEX IF NOT EXISTS idx_crm_contacts_last_contact ON crm_contacts(last_contact_at);
CREATE INDEX IF NOT EXISTS idx_crm_contacts_next_follow_up ON crm_contacts(next_follow_up_at);
