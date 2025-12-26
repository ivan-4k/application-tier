<?php
// application/config/Database.php

class Database {
    private string $host = "localhost";
    private string $db   = "klinik";
    private string $user = "root";
    private string $pass = "";
    private string $charset = "utf8mb4";

    public function getConnection(): PDO {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        return new PDO($dsn, $this->user, $this->pass, $options);
    }
}
