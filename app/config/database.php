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

        $eventColumns = array_column($db->query('PRAGMA table_info(events)')->fetchAll(), 'name');
        if (!in_array('artist_map_link', $eventColumns, true)) {
            $db->exec('ALTER TABLE events ADD COLUMN artist_map_link TEXT DEFAULT NULL');
        }
        if (!in_array('artist_details', $eventColumns, true)) {
            $db->exec('ALTER TABLE events ADD COLUMN artist_details TEXT DEFAULT NULL');
        }
    }
}
