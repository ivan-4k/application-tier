<?php
namespace App\Core;

// Use statements HARUS di sini, setelah namespace
use App\Config\Database;

class App {
    public function __construct() {
        // Register autoloader
        require_once __DIR__ . '/Autoloader.php';
        Autoloader::register();
        
        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Handle the request
        $this->handleRequest();
    }
    
    private function handleRequest() {
        // Simple routing
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Remove base path if exists
        $base_path = '/application-tier/public';
        if (strpos($uri, $base_path) === 0) {
            $uri = substr($uri, strlen($base_path));
        }
        
        // Default route
        if ($uri === '/' || $uri === '') {
            $this->showApiInfo();
            return;
        }
        
        // API routes
        switch ($uri) {
            case '/health':
                $this->healthCheck();
                break;
                
            case '/dokter':
                if ($method === 'GET') {
                    $this->getDokter();
                }
                break;
                
            case '/antrian':
                if ($method === 'POST') {
                    $this->createAntrian();
                } elseif ($method === 'GET') {
                    $this->getAntrian();
                }
                break;
                
            case '/antrian/aktif':
                if ($method === 'GET') {
                    $this->getAntrianAktif();
                }
                break;
                
            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Endpoint not found: ' . $uri
                ]);
        }
    }
    
    private function showApiInfo() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Klinik Antrian API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /health' => 'Health check',
                'GET /dokter' => 'Get all doctors',
                'POST /antrian' => 'Create new queue',
                'GET /antrian/aktif' => 'Get active queues',
                'GET /antrian/selesai' => 'Get completed queues'
            ]
        ], JSON_PRETTY_PRINT);
    }
    
    private function healthCheck() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Server is running',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function getDokter() {
        // Load database and get doctors
        require_once __DIR__ . '/../config/Database.php';
        
        try {
            $conn = Database::getConnection();
            $sql = "SELECT * FROM dokter";
            $result = $conn->query($sql);
            
            $dokter = [];
            while ($row = $result->fetch_assoc()) {
                $dokter[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => ['dokter' => $dokter]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function createAntrian() {
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON data'
            ]);
            return;
        }
        
        // Validate required fields
        $required = ['nama', 'umur', 'alamat', 'keluhan', 'dokter_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Field '$field' is required"
                ]);
                return;
            }
        }
        
        // Load database
        require_once __DIR__ . '/../config/Database.php';
        
        try {
            $conn = Database::getConnection();
            
            // 1. Cari atau buat pasien
            $sql = "SELECT id_pasien FROM pasien WHERE nama_pasien = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $input['nama']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $id_pasien = $row['id_pasien'];
            } else {
                // Buat pasien baru
                $sql = "INSERT INTO pasien (nama_pasien, alamat, umur, jenis_keluhan, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", 
                    $input['nama'],
                    $input['alamat'],
                    $input['umur'],
                    $input['keluhan']
                );
                
                if ($stmt->execute()) {
                    $id_pasien = $conn->insert_id;
                } else {
                    throw new \Exception("Gagal membuat pasien: " . $conn->error);
                }
            }
            
            // 2. Buat antrian
            $prefix = date('ymd');
            $sql = "SELECT COUNT(*) as count FROM antrian WHERE DATE(waktu_ambil) = CURDATE()";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $sequence = str_pad($row['count'] + 1, 3, '0', STR_PAD_LEFT);
            $nomor_antrian = $prefix . $sequence;
            
            $sql = "INSERT INTO antrian (nomor_antrian, id_pasien, id_dokter, status, waktu_ambil) 
                    VALUES (?, ?, ?, 'MENUNGGU', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $nomor_antrian, $id_pasien, $input['dokter_id']);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Antrian berhasil dibuat',
                    'data' => [
                        'nomorAntrian' => $nomor_antrian,
                        'nomor' => $nomor_antrian,
                        'id_antrian' => $conn->insert_id
                    ]
                ]);
            } else {
                throw new \Exception("Gagal membuat antrian: " . $conn->error);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getAntrianAktif() {
        require_once __DIR__ . '/../config/Database.php';
        
        try {
            $conn = Database::getConnection();
            
            $sql = "SELECT 
                        a.*,
                        p.nama_pasien,
                        p.alamat,
                        p.umur,
                        p.jenis_keluhan,
                        d.nama_dokter,
                        d.spesialis,
                        d.ruangan
                    FROM antrian a
                    LEFT JOIN pasien p ON a.id_pasien = p.id_pasien
                    LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
                    WHERE a.status IN ('MENUNGGU', 'DILAYANI')
                    ORDER BY a.waktu_ambil ASC";
            
            $result = $conn->query($sql);
            $antrian = [];
            
            while ($row = $result->fetch_assoc()) {
                // Format untuk Java client
                $antrian[] = [
                    'nomor' => $row['nomor_antrian'],
                    'id_antrian' => $row['id_antrian'],
                    'nama' => $row['nama_pasien'],
                    'umur' => $row['umur'],
                    'alamat' => $row['alamat'],
                    'keluhan' => $row['jenis_keluhan'],
                    'dokter' => $row['nama_dokter'],
                    'spesialis' => $row['spesialis'],
                    'ruangan' => $row['ruangan'],
                    'status' => $row['status'],
                    'waktu_ambil' => $row['waktu_ambil']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => ['antrian' => $antrian]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}
?>