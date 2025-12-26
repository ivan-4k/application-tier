<?php
class Dokter extends Model {

    public $id;
    public $nama_dokter;
    public $spesialis;
    public $ruangan;

    public function __construct($db) {
        parent::__construct($db);
        $this->table = "dokter";
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY id_dokter ASC";
        return $this->executeQuery($query);
    }

    public function getById() {
        $query = "SELECT * FROM {$this->table} WHERE id_dokter = :id LIMIT 1";
        $stmt = $this->executeQuery($query, [':id' => $this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "INSERT INTO {$this->table}
                  (nama_dokter, spesialis, ruangan)
                  VALUES (:nama, :spesialis, :ruangan)";

        return $this->executeQuery($query, [
            ':nama' => $this->nama_dokter,
            ':spesialis' => $this->spesialis,
            ':ruangan' => $this->ruangan
        ]);
    }
}
