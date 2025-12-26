<?php
// application/dao/DokterDAO.php

class DokterDAO {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function getAll(): array {
        $sql = "SELECT id_dokter, nama_dokter, spesialis, ruangan
                FROM dokter
                ORDER BY nama_dokter ASC";
        return $this->conn->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array {
        $sql = "SELECT id_dokter, nama_dokter, spesialis, ruangan
                FROM dokter WHERE id_dokter=:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
