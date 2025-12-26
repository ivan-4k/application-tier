<?php
class Pasien extends Model {

    public $id;
    public $nama_pasien;
    public $umur;
    public $alamat;
    public $jenis_keluhan;

    public function __construct($db) {
        parent::__construct($db);
        $this->table = "pasien";
    }

    public function create() {
        $query = "INSERT INTO {$this->table}
                  (nama_pasien, umur, alamat, jenis_keluhan)
                  VALUES (:nama, :umur, :alamat, :keluhan)";

        $stmt = $this->executeQuery($query, [
            ':nama' => $this->nama_pasien,
            ':umur' => $this->umur,
            ':alamat' => $this->alamat,
            ':keluhan' => $this->jenis_keluhan
        ]);

        if ($stmt) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
