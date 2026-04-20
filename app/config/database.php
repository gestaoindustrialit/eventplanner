<?php

class Database
{
    /** @var string */
    private $sqlitePath;

    public function __construct()
    {
        $defaultPath = dirname(__DIR__, 2) . '/database/eventplanner.sqlite';
        $this->sqlitePath = getenv('SQLITE_PATH') ?: $defaultPath;
    }


    public function getSqlitePath()
    {
        return $this->sqlitePath;
    }

    public function getConnection()
    {
        $dbDir = dirname($this->sqlitePath);
        if (!is_dir($dbDir) && !mkdir($dbDir, 0775, true) && !is_dir($dbDir)) {
            throw new RuntimeException('Não foi possível criar a pasta da base de dados: ' . $dbDir);
        }

        $dsn = "sqlite:{$this->sqlitePath}";

        $db = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $db->exec('PRAGMA foreign_keys = ON');
        $this->ensureSchema($db);

        return $db;
    }

    private function ensureSchema(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS public_pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                excerpt TEXT DEFAULT NULL,
                content TEXT DEFAULT NULL,
                hero_image_url TEXT DEFAULT NULL,
                display_mode TEXT NOT NULL DEFAULT \'section\' CHECK (display_mode IN (\'section\', \'page\')),
                section_type TEXT NOT NULL DEFAULT \'default\' CHECK (section_type IN (\'default\', \'about\', \'services\', \'contact_form\')),
                section_style TEXT NOT NULL DEFAULT \'card\' CHECK (section_style IN (\'card\', \'split\', \'icons\', \'highlight\')),
                section_config_json TEXT DEFAULT NULL,
                is_published INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $publicPageColumns = array_column($db->query('PRAGMA table_info(public_pages)')->fetchAll(), 'name');
        if (!in_array('display_mode', $publicPageColumns, true)) {
            $db->exec('ALTER TABLE public_pages ADD COLUMN display_mode TEXT NOT NULL DEFAULT \'section\'');
        }
        if (!in_array('section_type', $publicPageColumns, true)) {
            $db->exec('ALTER TABLE public_pages ADD COLUMN section_type TEXT NOT NULL DEFAULT \'default\'');
        }
        if (!in_array('section_style', $publicPageColumns, true)) {
            $db->exec('ALTER TABLE public_pages ADD COLUMN section_style TEXT NOT NULL DEFAULT \'card\'');
        }
        if (!in_array('section_config_json', $publicPageColumns, true)) {
            $db->exec('ALTER TABLE public_pages ADD COLUMN section_config_json TEXT DEFAULT NULL');
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                name TEXT DEFAULT NULL,
                gdpr_consent INTEGER NOT NULL DEFAULT 0,
                consent_text TEXT NOT NULL DEFAULT \'\',
                source TEXT DEFAULT NULL,
                status TEXT NOT NULL DEFAULT \'active\' CHECK (status IN (\'active\', \'unsubscribed\')),
                subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS site_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS press_contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT DEFAULT NULL,
                locality TEXT DEFAULT NULL,
                district TEXT DEFAULT NULL,
                website TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pressContactColumns = array_column($db->query('PRAGMA table_info(press_contacts)')->fetchAll(), 'name');
        if (!in_array('website', $pressContactColumns, true)) {
            $db->exec('ALTER TABLE press_contacts ADD COLUMN website TEXT DEFAULT NULL');
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS checklist_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS checklist_template_fields (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                template_id INTEGER NOT NULL,
                label TEXT NOT NULL,
                field_type TEXT NOT NULL DEFAULT \'checkbox\' CHECK (field_type IN (\'checkbox\', \'text\')),
                is_required INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE CASCADE
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS event_checklists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL UNIQUE,
                template_id INTEGER DEFAULT NULL,
                name TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE SET NULL
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS event_checklist_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_checklist_id INTEGER NOT NULL,
                label TEXT NOT NULL,
                field_type TEXT NOT NULL DEFAULT \'checkbox\' CHECK (field_type IN (\'checkbox\', \'text\')),
                is_required INTEGER NOT NULL DEFAULT 0,
                value TEXT DEFAULT NULL,
                is_checked INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_checklist_id) REFERENCES event_checklists(id) ON DELETE CASCADE
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS event_schedule_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                starts_at TEXT NOT NULL,
                duration_minutes INTEGER NOT NULL DEFAULT 15,
                item_type TEXT NOT NULL DEFAULT \'artist\' CHECK (item_type IN (\'artist\', \'break\', \'technical\', \'doors\', \'other\')),
                title TEXT NOT NULL,
                responsible TEXT DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
            )'
        );


        $db->exec(
            'CREATE TABLE IF NOT EXISTS crm_contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                entity_name TEXT NOT NULL,
                contact_name TEXT DEFAULT NULL,
                email TEXT DEFAULT NULL,
                phone TEXT DEFAULT NULL,
                website TEXT DEFAULT NULL,
                social_profile TEXT DEFAULT NULL,
                country TEXT DEFAULT NULL,
                city TEXT DEFAULT NULL,
                market TEXT NOT NULL DEFAULT \'nacional\' CHECK (market IN (\'nacional\', \'internacional\')),
                contact_type TEXT NOT NULL DEFAULT \'outro\' CHECK (contact_type IN (\'venda\', \'apoio\', \'parceria\', \'patrocinador\', \'media\', \'fornecedor\', \'outro\')),
                status TEXT NOT NULL DEFAULT \'novo\' CHECK (status IN (\'novo\', \'por_contactar\', \'contactado\', \'aguardar_resposta\', \'em_negociacao\', \'proposta_enviada\', \'interessado\', \'fechado_ganho\', \'fechado_perdido\', \'sem_interesse\', \'arquivado\')),
                priority TEXT NOT NULL DEFAULT \'media\' CHECK (priority IN (\'baixa\', \'media\', \'alta\', \'urgente\')),
                lead_source TEXT DEFAULT NULL,
                first_contact_at TEXT DEFAULT NULL,
                last_contact_at TEXT DEFAULT NULL,
                next_follow_up_at TEXT DEFAULT NULL,
                potential_value NUMERIC DEFAULT NULL,
                observations TEXT DEFAULT NULL,
                internal_notes TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS crm_contact_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                contact_id INTEGER NOT NULL,
                note TEXT NOT NULL,
                created_by_user_id INTEGER DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contact_id) REFERENCES crm_contacts(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            )'
        );

        $db->exec('CREATE INDEX IF NOT EXISTS idx_crm_contacts_status ON crm_contacts(status)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_crm_contacts_type ON crm_contacts(contact_type)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_crm_contacts_market ON crm_contacts(market)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_crm_contacts_priority ON crm_contacts(priority)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_crm_contacts_last_contact ON crm_contacts(last_contact_at)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_crm_contacts_next_follow_up ON crm_contacts(next_follow_up_at)');
        $db->exec(
            'CREATE TABLE IF NOT EXISTS partners (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_name TEXT NOT NULL,
                logo_url TEXT NOT NULL,
                company_url TEXT DEFAULT NULL,
                partnership_start_date TEXT NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        if ($this->tableExists($db, 'comedians')) {
            $columns = $db->query('PRAGMA table_info(comedians)')->fetchAll();
            $comedianColumns = array_column($columns, 'name');

            if (!in_array('bio', $comedianColumns, true)) {
                $db->exec('ALTER TABLE comedians ADD COLUMN bio TEXT DEFAULT NULL');
            }
            if (!in_array('city', $comedianColumns, true)) {
                $db->exec('ALTER TABLE comedians ADD COLUMN city TEXT DEFAULT NULL');
            }
            if (!in_array('attachment_path', $comedianColumns, true)) {
                $db->exec('ALTER TABLE comedians ADD COLUMN attachment_path TEXT DEFAULT NULL');
            }
            if (!in_array('price_bar', $comedianColumns, true)) {
                $db->exec('ALTER TABLE comedians ADD COLUMN price_bar NUMERIC DEFAULT 0');
            }
            if (!in_array('price_auditorium', $comedianColumns, true)) {
                $db->exec('ALTER TABLE comedians ADD COLUMN price_auditorium NUMERIC DEFAULT 0');
            }
        }

        if ($this->tableExists($db, 'events')) {
            $eventColumns = array_column($db->query('PRAGMA table_info(events)')->fetchAll(), 'name');
            if (!in_array('is_visible', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN is_visible INTEGER NOT NULL DEFAULT 1');
            }
            if (!in_array('artist_map_link', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN artist_map_link TEXT DEFAULT NULL');
            }
            if (!in_array('artist_details', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN artist_details TEXT DEFAULT NULL');
            }
            if (!in_array('poster_url', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN poster_url TEXT DEFAULT NULL');
            }
            if (!in_array('external_ticket_url', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN external_ticket_url TEXT DEFAULT NULL');
            }
            if (!in_array('reservations_open', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN reservations_open INTEGER NOT NULL DEFAULT 1');
            }
            if (!in_array('reservation_capacity', $eventColumns, true)) {
                $db->exec('ALTER TABLE events ADD COLUMN reservation_capacity INTEGER NOT NULL DEFAULT 0');
            }
        }
    }

    private function tableExists(PDO $db, string $table): bool
    {
        $stmt = $db->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table LIMIT 1");
        $stmt->execute(['table' => $table]);
        return (bool)$stmt->fetchColumn();
    }
}
