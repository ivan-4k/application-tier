<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Dokter;

class DokterController extends Controller {
    private $dokterModel;
    
    public function __construct() {
        $this->handleOptions();
        $this->dokterModel = new Dokter();
    }
    
    public function index() {
        try {
            $dokter = $this->dokterModel->getAllActive();
            
            $this->success('Data dokter berhasil diambil', [
                'dokter' => $dokter,
                'total' => count($dokter)
            ]);
            
        } catch (\Exception $e) {
            $this->error('Gagal mengambil data dokter: ' . $e->getMessage());
        }
    }
    
    public function show($id) {
        try {
            $dokter = $this->dokterModel->getById($id);
            
            if ($dokter) {
                $this->success('Data dokter berhasil diambil', $dokter);
            } else {
                $this->error('Dokter tidak ditemukan', 404);
            }
            
        } catch (\Exception $e) {
            $this->error('Gagal mengambil data dokter: ' . $e->getMessage());
        }
    }
}
?>