<?php
// application/dao/AntrianDAO.php

class AntrianDAO {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function getNextNomor(): int {
        $sql = "SELECT IFNULL(MAX(nomor_antrian),0)+1 AS next_nomor FROM antrian";
        $row = $this->conn->query($sql)->fetch();
        return (int)$row["next_nomor"];
    }

    // CREATE: buat antrian (status default MENUNGGU)
    public function insert(int $nomor, int $idPasien, int $idDokter): void {
        $sql = "INSERT INTO antrian(nomor_antrian, id_pasien, id_dokter, status, waktu_ambil)
                VALUES(:nomor, :id_pasien, :id_dokter, 'MENUNGGU', NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":nomor" => $nomor,
            ":id_pasien" => $idPasien,
            ":id_dokter" => $idDokter,
        ]);
    }

    // READ: antrian aktif (MENUNGGU & DILAYANI)
    public function listAktif(): array {
        $sql = "SELECT
                    a.nomor_antrian,
                    a.status,
                    a.waktu_ambil,
                    a.waktu_mulai,
                    p.id_pasien, p.nama_pasien, p.jenis_keluhan, p.alamat, p.umur,
                    d.id_dokter, d.nama_dokter, d.spesialis, d.ruangan
                FROM antrian a
                JOIN pasien p ON a.id_pasien=p.id_pasien
                JOIN dokter d ON a.id_dokter=d.id_dokter
                WHERE a.status IN ('MENUNGGU','DILAYANI')
                ORDER BY a.nomor_antrian ASC";
        return $this->conn->query($sql)->fetchAll();
    }

    // READ: antrian selesai
    public function listSelesai(): array {
        $sql = "SELECT
                    a.nomor_antrian,
                    a.status,
                    a.waktu_ambil,
                    a.waktu_mulai,
                    a.waktu_selesai,
                    p.id_pasien, p.nama_pasien, p.jenis_keluhan, p.alamat, p.umur,
                    d.id_dokter, d.nama_dokter, d.spesialis, d.ruangan
                FROM antrian a
                JOIN pasien p ON a.id_pasien=p.id_pasien
                JOIN dokter d ON a.id_dokter=d.id_dokter
                WHERE a.status='SELESAI'
                ORDER BY a.waktu_selesai DESC";
        return $this->conn->query($sql)->fetchAll();
    }

    // UPDATE status: DILAYANI / SELESAI
    public function updateStatus(int $nomor, string $status): void {
        $status = strtoupper($status);
        if (!in_array($status, ["DILAYANI", "SELESAI"], true)) {
            throw new Exception("Status tidak valid");
        }

        $col = ($status === "DILAYANI") ? "waktu_mulai" : "waktu_selesai";
        $sql = "UPDATE antrian SET status=:status, {$col}=NOW()
                WHERE nomor_antrian=:nomor";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":status" => $status,
            ":nomor" => $nomor
        ]);
    }

    // Statistik
    public function countStatus(): array {
        $sql = "SELECT
                SUM(CASE WHEN status='MENUNGGU' THEN 1 ELSE 0 END) AS menunggu,
                SUM(CASE WHEN status='DILAYANI' THEN 1 ELSE 0 END) AS dilayani,
                SUM(CASE WHEN status='SELESAI' THEN 1 ELSE 0 END) AS selesai
                FROM antrian";
        return $this->conn->query($sql)->fetch();
    }
}
