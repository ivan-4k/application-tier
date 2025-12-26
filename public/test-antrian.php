<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/models/Antrian.php';

$db = (new Database())->getConnection();
$antrian = new Antrian($db);

/* ===== TEST AMBIL NOMOR ===== */
$antrian->nomor_antrian = $antrian->getNomorTerakhir();

/* ===== SET RELASI ===== */
// pastikan ID INI ADA di database
$antrian->id_pasien = 1;
$antrian->id_dokter = 1;

/* ===== CREATE ===== */
if ($antrian->create()) {
    echo "✅ Antrian berhasil ditambahkan\n";
} else {
    echo "❌ Gagal menambahkan antrian\n";
}

/* ===== READ ===== */
echo "\n📋 DAFTAR ANTRIAN:\n";
print_r($antrian->getAll());
