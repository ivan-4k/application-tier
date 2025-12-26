<?php
namespace App\Services;

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Dokter;

class AntrianService {
    private $antrianModel;
    private $pasienModel;
    private $dokterModel;
    
    public function __construct() {
        $this->antrianModel = new Antrian();
        $this->pasienModel = new Pasien();
        $this->dokterModel = new Dokter();
    }
    
    public function takeQueue($data) {
        // Validate input
        $errors = $this->validateQueueData($data);
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
        
        // Check doctor exists
        $dokter = $this->dokterModel->getById($data['dokter_id']);
        if (!$dokter) {
            throw new \Exception('Dokter tidak ditemukan');
        }
        
        // Start transaction
        $this->antrianModel->query("START TRANSACTION");
        
        try {
            // 1. Save patient
            $pasienData = [
                'nama' => $data['nama'],
                'umur' => $data['umur'],
                'alamat' => $data['alamat']
            ];
            
            $pasienId = $this->pasienModel->create($pasienData);
            
            // 2. Generate queue number
            $queueNumber = $this->antrianModel->generateQueueNumber();
            
            // 3. Save queue
            $antrianData = [
                'nomor_antrian' => $queueNumber,
                'id_pasien' => $pasienId,
                'id_dokter' => $data['dokter_id'],
                'keluhan' => $data['keluhan'],
                'status' => 'MENUNGGU'
            ];
            
            $this->antrianModel->createQueue($antrianData);
            
            // Commit transaction
            $this->antrianModel->query("COMMIT");
            
            return [
                'nomor' => $queueNumber,
                'nama' => $data['nama'],
                'umur' => $data['umur'],
                'alamat' => $data['alamat'],
                'keluhan' => $data['keluhan'],
                'dokter' => $dokter['nama'],
                'spesialis' => $dokter['spesialis'],
                'ruangan' => $dokter['ruangan'],
                'status' => 'MENUNGGU',
                'waktu_daftar' => date('d/m/Y H:i')
            ];
            
        } catch (\Exception $e) {
            $this->antrianModel->query("ROLLBACK");
            throw $e;
        }
    }
    
    private function validateQueueData($data) {
        $errors = [];
        
        if (empty($data['nama'])) {
            $errors[] = 'Nama tidak boleh kosong';
        }
        
        if (empty($data['umur']) || $data['umur'] < 1 || $data['umur'] > 120) {
            $errors[] = 'Umur tidak valid (1-120 tahun)';
        }
        
        if (empty($data['alamat'])) {
            $errors[] = 'Alamat tidak boleh kosong';
        }
        
        if (empty($data['keluhan'])) {
            $errors[] = 'Keluhan tidak boleh kosong';
        }
        
        if (empty($data['dokter_id'])) {
            $errors[] = 'Dokter harus dipilih';
        }
        
        return $errors;
    }
    
    public function getActiveQueues() {
        return $this->antrianModel->getActiveQueues();
    }
    
    public function getCompletedQueues($limit = 50) {
        return $this->antrianModel->getCompletedQueues($limit);
    }
    
    public function updateQueueStatus($queueNumber, $status) {
        $validStatus = ['MENUNGGU', 'DILAYANI', 'SELESAI', 'BATAL'];
        
        if (!in_array($status, $validStatus)) {
            throw new \Exception('Status tidak valid');
        }
        
        $result = $this->antrianModel->updateStatus($queueNumber, $status);
        
        if (!$result) {
            throw new \Exception('Gagal mengupdate status antrian');
        }
        
        return $this->getQueueByNumber($queueNumber);
    }
    
    public function getQueueByNumber($queueNumber) {
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
                    DATE_FORMAT(a.waktu_daftar, '%d/%m/%Y %H:%i') as waktu_daftar
                FROM antrian a
                JOIN pasien p ON a.id_pasien = p.id_pasien
                JOIN dokter d ON a.id_dokter = d.id_dokter
                WHERE a.nomor_antrian = ?";
        
        $result = $this->antrianModel->query($sql, [$queueNumber]);
        return $result->fetch_assoc();
    }
    
    public function getStatistics() {
        return $this->antrianModel->getStatistics();
    }
}
?>