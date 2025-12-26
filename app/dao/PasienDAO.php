<?php
// application/dao/PasienDAO.php

class PasienDAO {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function insert(string $nama, string $keluhan, string $alamat, int $umur): int {
        $sql = "INSERT INTO pasien(nama_pasien, jenis_keluhan, alamat, umur)
                VALUES(:nama, :keluhan, :alamat, :umur)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":nama" => $nama,
            ":keluhan" => $keluhan,
            ":alamat" => $alamat,
            ":umur" => $umur,
        ]);
        return (int)$this->conn->lastInsertId();
    }

    public function findById(int $id): ?array {
        $sql = "SELECT id_pasien, nama_pasien, jenis_keluhan, alamat, umur
                FROM pasien WHERE id_pasien=:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
