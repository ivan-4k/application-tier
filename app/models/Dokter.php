<?php
namespace App\Models;

use App\Core\Model;

class Dokter extends Model {
    protected $table = 'dokter';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllActive() {
        $sql = "SELECT 
                    id_dokter as id,
                    nama_dokter as nama,
                    spesialis,
                    ruangan,
                    created_at
                FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY nama_dokter";
        
        $result = $this->query($sql);
        
        $dokter = [];
        while ($row = $result->fetch_assoc()) {
            $dokter[] = $row;
        }
        
        return $dokter;
    }
    
    public function getById($id) {
        $sql = "SELECT 
                    id_dokter as id,
                    nama_dokter as nama,
                    spesialis,
                    ruangan
                FROM {$this->table} 
                WHERE id_dokter = ? AND is_active = 1";
        
        $result = $this->query($sql, [$id]);
        
        return $result->fetch_assoc();
    }
}
?>