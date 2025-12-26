<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Config\Config;

class HomeController extends Controller {
    public function index() {
        $this->success('Klinik Antrian API', [
            'name' => Config::APP_NAME,
            'version' => Config::APP_VERSION,
            'environment' => Config::APP_ENV,
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'GET /api' => 'API Documentation',
                'GET /api/dokter' => 'Get all doctors',
                'GET /api/dokter/{id}' => 'Get doctor by ID',
                'GET /api/antrian/aktif' => 'Get active queues',
                'GET /api/antrian/selesai' => 'Get completed queues',
                'GET /api/antrian/statistik' => 'Get statistics',
                'POST /api/antrian' => 'Take new queue',
                'PUT /api/antrian/{nomor}/status' => 'Update queue status'
            ]
        ]);
    }
    
    public function health() {
        $this->success('Service is healthy', [
            'status' => 'UP',
            'service' => Config::APP_NAME,
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => 'Connected'
        ]);
    }
}
?>