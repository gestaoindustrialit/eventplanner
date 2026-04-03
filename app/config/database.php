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

        return $db;
    }
}
