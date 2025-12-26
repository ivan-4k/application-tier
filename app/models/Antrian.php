<?php
namespace App\Models;

use App\Core\Model;

class Antrian extends Model {
    protected $table = 'antrian';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function generateQueueNumber() {
        $datePart = date('ymd');
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE DATE(waktu_daftar) = CURDATE()";
        
        $result = $this->query($sql);
        $count = $result->fetch_assoc()['total'];
        
        $sequence = str_pad(($count + 1), 4, '0', STR_PAD_LEFT);
        return $datePart . $sequence;
    }
    
    public function getActiveQueues() {
        $sql = "SELECT 
                    a.nomor_antrian as nomor,
                    p.nama,
                    p.umur,
                    p.alamat,
                    a.keluhan,
                    d.nama_dokter as dokter,
                    d.spesialis,
                    d.ruangan,
                    a.status,
                    DATE_FORMAT(a.waktu_daftar, '%d/%m/%Y %H:%i') as waktu_daftar,
                    DATE_FORMAT(a.waktu_dilayani, '%d/%m/%Y %H:%i') as waktu_dilayani
                FROM antrian a
                JOIN pasien p ON a.id_pasien = p.id_pasien
                JOIN dokter d ON a.id_dokter = d.id_dokter
                WHERE a.status IN ('MENUNGGU', 'DILAYANI')
                ORDER BY 
                    CASE WHEN a.status = 'DILAYANI' THEN 1 ELSE 2 END,
                    a.waktu_daftar";
        
        $result = $this->query($sql);
        
        $queues = [];
        while ($row = $result->fetch_assoc()) {
            $queues[] = $row;
        }
        
        return $queues;
    }
    
    public function getCompletedQueues($limit = 50) {
        $sql = "SELECT 
                    a.nomor_antrian as nomor,
                    p.nama,
                    p.umur,
                    p.alamat,
                    a.keluhan,
                    d.nama_dokter as dokter,
                    a.status,
                    DATE_FORMAT(a.waktu_selesai, '%d/%m/%Y %H:%i') as waktu_selesai
                FROM antrian a
                JOIN pasien p ON a.id_pasien = p.id_pasien
                JOIN dokter d ON a.id_dokter = d.id_dokter
                WHERE a.status = 'SELESAI'
                ORDER BY a.waktu_selesai DESC
                LIMIT ?";
        
        $result = $this->query($sql, [$limit]);
        
        $queues = [];
        while ($row = $result->fetch_assoc()) {
            $queues[] = $row;
        }
        
        return $queues;
    }
    
    public function getStatistics() {
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'MENUNGGU' THEN 1 ELSE 0 END) as menunggu,
                    SUM(CASE WHEN status = 'DILAYANI' THEN 1 ELSE 0 END) as dilayani,
                    SUM(CASE WHEN status = 'SELESAI' THEN 1 ELSE 0 END) as selesai,
                    COUNT(*) as total
                FROM {$this->table}";
        
        $result = $this->query($sql);
        return $result->fetch_assoc();
    }
    
    public function updateStatus($queueNumber, $status) {
        $updateData = ['status' => $status];
        
        if ($status === 'DILAYANI') {
            $updateData['waktu_dilayani'] = date('Y-m-d H:i:s');
        } elseif ($status === 'SELESAI') {
            $updateData['waktu_selesai'] = date('Y-m-d H:i:s');
        }
        
        return $this->update(['nomor_antrian' => $queueNumber], $updateData);
    }
    
    public function createQueue($data) {
        $data['waktu_daftar'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }
}
?>