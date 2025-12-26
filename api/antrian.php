<?php
header("Content-Type: application/json");

require_once __DIR__ . "/../config/Database.php";
require_once __DIR__ . "/../dao/PasienDAO.php";
require_once __DIR__ . "/../dao/DokterDAO.php";
require_once __DIR__ . "/../dao/AntrianDAO.php";

$db = new Database();
$conn = $db->getConnection();

$pasienDAO  = new PasienDAO($conn);
$dokterDAO  = new DokterDAO($conn);
$antrianDAO = new AntrianDAO($conn);

$action = $_GET["action"] ?? "";

try {
    if ($action === "dokter") {
        echo json_encode($dokterDAO->getAll());
        exit;
    }

    if ($action === "listAktif") {
        echo json_encode($antrianDAO->listAktif());
        exit;
    }

    if ($action === "listSelesai") {
        echo json_encode($antrianDAO->listSelesai());
        exit;
    }

    if ($action === "stat") {
        echo json_encode($antrianDAO->countStatus());
        exit;
    }

    // POST: ambil antrian
    if ($action === "ambil" && $_SERVER["REQUEST_METHOD"] === "POST") {
        $nama    = $_POST["nama"] ?? "";
        $keluhan = $_POST["keluhan"] ?? "";
        $alamat  = $_POST["alamat"] ?? "";
        $umur    = (int)($_POST["umur"] ?? 0);
        $idDokter= (int)($_POST["id_dokter"] ?? 0);

        if ($nama === "" || $keluhan === "" || $alamat === "" || $umur <= 0 || $idDokter <= 0) {
            throw new Exception("Data tidak lengkap");
        }

        // transaksi biar aman
        $conn->beginTransaction();
        $idPasien = $pasienDAO->insert($nama, $keluhan, $alamat, $umur);
        $nomor = $antrianDAO->getNextNomor();
        $antrianDAO->insert($nomor, $idPasien, $idDokter);
        $conn->commit();

        echo json_encode(["ok" => true, "nomor" => $nomor]);
        exit;
    }

    // POST: update status
    if ($action === "status" && $_SERVER["REQUEST_METHOD"] === "POST") {
        $nomor  = (int)($_POST["nomor"] ?? 0);
        $status = $_POST["status"] ?? "";

        if ($nomor <= 0 || $status === "") throw new Exception("Input tidak valid");

        $antrianDAO->updateStatus($nomor, $status);
        echo json_encode(["ok" => true]);
        exit;
    }

    echo json_encode(["error" => "Action tidak dikenal"]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
