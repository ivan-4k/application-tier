<?php
// public/index.php

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

// Simple router
switch ($uri) {
    case '/':
    case '':
    case '/health':
        echo json_encode([
            'success' => true,
            'message' => 'Klinik API Server is running',
            'timestamp' => date('Y-m-d H:i:s')
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
        
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Endpoint not found: ' . $uri
        ]);
}

// ==== FUNCTIONS ====
function getDokter() {
    try {
        // Include database
        require_once __DIR__ . '/../app/config/Database.php';
        require_once __DIR__ . '/../app/config/Config.php';
        
        $conn = App\Config\Database::getConnection();
        
        // TAMBAHKAN ORDER BY id_dokter ASC
        $sql = "SELECT 
                    id_dokter as id,
                    nama_dokter as nama,
                    spesialis,
                    ruangan
                FROM dokter 
                ORDER BY id_dokter ASC";  // ← INI YANG DITAMBAH
        
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
?>