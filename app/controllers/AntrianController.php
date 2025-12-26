<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AntrianService;

class AntrianController extends Controller {
    private $antrianService;
    
    public function __construct() {
        $this->handleOptions();
        $this->antrianService = new AntrianService();
    }
    
    public function index() {
        $this->success('API Antrian Klinik', [
            'endpoints' => [
                'GET /api/antrian' => 'Get all active queues',
                'GET /api/antrian/selesai' => 'Get completed queues',
                'GET /api/antrian/statistik' => 'Get statistics',
                'POST /api/antrian' => 'Take new queue',
                'PUT /api/antrian/{nomor}/status' => 'Update queue status'
            ]
        ]);
    }
    
    public function aktif() {
        try {
            $queues = $this->antrianService->getActiveQueues();
            
            $this->success('Data antrian aktif berhasil diambil', [
                'antrian' => $queues,
                'total' => count($queues)
            ]);
            
        } catch (\Exception $e) {
            $this->error('Gagal mengambil antrian aktif: ' . $e->getMessage());
        }
    }
    
    public function selesai() {
        try {
            $queues = $this->antrianService->getCompletedQueues();
            
            $this->success('Data antrian selesai berhasil diambil', [
                'antrian' => $queues,
                'total' => count($queues)
            ]);
            
        } catch (\Exception $e) {
            $this->error('Gagal mengambil antrian selesai: ' . $e->getMessage());
        }
    }
    
    public function statistik() {
        try {
            $statistics = $this->antrianService->getStatistics();
            
            $this->success('Statistik berhasil diambil', $statistics);
            
        } catch (\Exception $e) {
            $this->error('Gagal mengambil statistik: ' . $e->getMessage());
        }
    }
    
    public function store() {
        try {
            $input = $this->getInput();
            
            $queueData = $this->antrianService->takeQueue($input);
            
            $this->success('Antrian berhasil diambil. Nomor Antrian: ' . $queueData['nomor'], $queueData);
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    public function updateStatus($nomor) {
        try {
            $input = $this->getInput();
            
            if (empty($input['status'])) {
                $this->error('Status tidak boleh kosong');
                return;
            }
            
            $updatedQueue = $this->antrianService->updateQueueStatus($nomor, $input['status']);
            
            $this->success('Status antrian berhasil diupdate', $updatedQueue);
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
?>