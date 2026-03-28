<?php

class Database
{
    private string $host = '127.0.0.1';
    private string $dbName = 'eventplanner';
    private string $username = 'root';
    private string $password = '';
    private string $charset = 'utf8mb4';

    public function getConnection(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";

        return new PDO($dsn, $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
