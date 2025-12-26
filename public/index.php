<?php
// public/index.php

// START OUTPUT BUFFERING
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==== HARUS DI ATAS SEMUA OUTPUT ====
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==== ROUTING ====
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remove query string
$uri = parse_url($request_uri, PHP_URL_PATH);

// Remove base path if using virtual host
$base_path = '/application-tier/public';
if (strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}

// CLEAN OUTPUT BUFFER
ob_clean();

// Simple router
switch ($uri) {
    case '/':
    case '':
    case '/health':
        echo json_encode([
            'success' => true,
            'message' => 'Klinik API Server is running',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                '/dokter' => 'GET - Get all doctors',
                '/antrian' => 'POST - Create new queue',
                '/antrian/aktif' => 'GET - Get active queues',
                '/antrian/selesai' => 'GET - Get completed queues'
            ]
        ]);
        break;
        
    case '/dokter':
        if ($method === 'GET') {
            getDokter();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed. Use GET'
            ]);
        }
        break;
        
    case '/antrian':
        if ($method === 'POST') {
            createAntrian();
        } elseif ($method === 'GET') {
            getAllAntrian();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed. Use POST or GET'
            ]);
        }
        break;
        
    case '/antrian/aktif':
        if ($method === 'GET') {
            getAntrianAktif();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed. Use GET'
            ]);
        }
        break;
        
    case '/antrian/selesai':
        if ($method === 'GET') {
            getAntrianSelesai();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed. Use GET'
            ]);
        }
        break;
        
    default:
        // Check for pattern /antrian/{id}/status
        if (preg_match('/^\/antrian\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
            $nomorAntrian = $matches[1];
            updateStatusAntrian($nomorAntrian);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Endpoint not found: ' . $uri
            ]);
        }
}

// END OUTPUT BUFFERING
ob_end_flush();

// ==== FUNCTIONS ====
function getDokter() {
    try {
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
        $sql = "SELECT 
                    id_dokter as id,
                    nama_dokter as nama,
                    spesialis,
                    ruangan
                FROM dokter 
                ORDER BY id_dokter ASC";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $dokter = [];
        while ($row = $result->fetch_assoc()) {
            $dokter[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Data dokter berhasil diambil',
            'data' => [
                'dokter' => $dokter
            ],
            'count' => count($dokter)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function createAntrian() {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception("Invalid JSON data");
        }
        
        // Validate required fields
        $required = ['nama', 'umur', 'alamat', 'keluhan', 'dokter_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
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
                throw new Exception("Gagal membuat pasien: " . $conn->error);
            }
        }
        
        // 2. Generate nomor antrian
        $prefix = date('ymd');
        $sql = "SELECT COUNT(*) as count FROM antrian WHERE DATE(waktu_ambil) = CURDATE()";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $sequence = str_pad($row['count'] + 1, 3, '0', STR_PAD_LEFT);
        $nomor_antrian = $prefix . $sequence;
        
        // 3. Buat antrian
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
            throw new Exception("Gagal membuat antrian: " . $conn->error);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function getAntrianAktif() {
    try {
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
        $sql = "SELECT 
                    a.nomor_antrian as nomor,
                    a.id_antrian,
                    p.nama_pasien as nama,
                    p.umur,
                    p.alamat,
                    p.jenis_keluhan as keluhan,
                    d.nama_dokter as dokter,
                    d.spesialis,
                    d.ruangan,
                    a.status,
                    a.waktu_ambil,
                    a.waktu_mulai,
                    a.waktu_selesai
                FROM antrian a
                LEFT JOIN pasien p ON a.id_pasien = p.id_pasien
                LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
                WHERE a.status IN ('MENUNGGU', 'DILAYANI')
                ORDER BY a.waktu_ambil ASC";
        
        $result = $conn->query($sql);
        
        $antrian = [];
        while ($row = $result->fetch_assoc()) {
            $antrian[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Data antrian aktif berhasil diambil',
            'data' => [
                'antrian' => $antrian
            ],
            'count' => count($antrian)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function getAntrianSelesai() {
    try {
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
        $sql = "SELECT 
                    a.nomor_antrian as nomor,
                    a.id_antrian,
                    p.nama_pasien as nama,
                    p.umur,
                    p.alamat,
                    p.jenis_keluhan as keluhan,
                    d.nama_dokter as dokter,
                    d.spesialis,
                    d.ruangan,
                    a.status,
                    a.waktu_ambil,
                    a.waktu_mulai,
                    a.waktu_selesai,
                    a.catatan
                FROM antrian a
                LEFT JOIN pasien p ON a.id_pasien = p.id_pasien
                LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
                WHERE a.status IN ('SELESAI', 'BATAL')
                ORDER BY a.waktu_selesai DESC
                LIMIT 50";
        
        $result = $conn->query($sql);
        
        $antrian = [];
        while ($row = $result->fetch_assoc()) {
            $antrian[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Data antrian selesai berhasil diambil',
            'data' => [
                'antrian' => $antrian
            ],
            'count' => count($antrian)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function getAllAntrian() {
    try {
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
        $sql = "SELECT 
                    a.nomor_antrian as nomor,
                    a.id_antrian,
                    p.nama_pasien as nama,
                    p.umur,
                    p.alamat,
                    p.jenis_keluhan as keluhan,
                    d.nama_dokter as dokter,
                    d.spesialis,
                    d.ruangan,
                    a.status,
                    a.waktu_ambil
                FROM antrian a
                LEFT JOIN pasien p ON a.id_pasien = p.id_pasien
                LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
                ORDER BY a.waktu_ambil DESC
                LIMIT 100";
        
        $result = $conn->query($sql);
        
        $antrian = [];
        while ($row = $result->fetch_assoc()) {
            $antrian[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Semua data antrian berhasil diambil',
            'data' => [
                'antrian' => $antrian
            ],
            'count' => count($antrian)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function updateStatusAntrian($nomorAntrian) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['status'])) {
            throw new Exception("Status is required");
        }
        
        $status = strtoupper($input['status']);
        $validStatus = ['MENUNGGU', 'DILAYANI', 'SELESAI', 'BATAL'];
        
        if (!in_array($status, $validStatus)) {
            throw new Exception("Invalid status. Must be: " . implode(', ', $validStatus));
        }
        
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
        // Update berdasarkan nomor_antrian (bukan id_antrian)
        $sql = "UPDATE antrian SET status = ?";
        
        // Tambahkan timestamp berdasarkan status
        if ($status === 'DILAYANI') {
            $sql .= ", waktu_mulai = NOW()";
        } elseif ($status === 'SELESAI' || $status === 'BATAL') {
            $sql .= ", waktu_selesai = NOW()";
        }
        
        $sql .= " WHERE nomor_antrian = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $status, $nomorAntrian);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "Status antrian $nomorAntrian berhasil diupdate ke $status"
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Antrian dengan nomor $nomorAntrian tidak ditemukan"
                ]);
            }
        } else {
            throw new Exception("Gagal update status: " . $conn->error);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>