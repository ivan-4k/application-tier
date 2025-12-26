<?php
class Antrian extends Model {

    public $id;
    public $nomor_antrian;
    public $id_pasien;
    public $id_dokter;
    public $status;

    public function __construct($db) {
        parent::__construct($db);
        $this->table = "antrian";
    }

    public function getNomorTerakhir() {
        $query = "SELECT IFNULL(MAX(nomor_antrian),0) + 1 AS next_no FROM {$this->table}";
        $stmt = $this->executeQuery($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['next_no'];
    }

    public function create() {
        $query = "INSERT INTO {$this->table}
                  (nomor_antrian, id_pasien, id_dokter, status, waktu_ambil)
                  VALUES (:no, :pasien, :dokter, 'MENUNGGU', NOW())";

        return $this->executeQuery($query, [
            ':no' => $this->nomor_antrian,
            ':pasien' => $this->id_pasien,
            ':dokter' => $this->id_dokter
        ]);
    }

    public function updateStatus($status) {
        $query = "UPDATE {$this->table}
                  SET status = :status
                  WHERE nomor_antrian = :no";

        return $this->executeQuery($query, [
            ':status' => $status,
            ':no' => $this->nomor_antrian
        ]);
    }
}
