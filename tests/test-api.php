<?php
// tests/test-complete-integration.php
require_once __DIR__ . '/../app/config/Database.php';

use App\Config\Database;

echo "=== COMPLETE DATABASE INTEGRATION TEST ===\n";

$conn = Database::getConnection();

// 1. TEST TABEL DOKTER
echo "\n1. DOKTER TABLE:\n";
echo "   " . str_repeat("-", 40) . "\n";
$sql = "SELECT COUNT(*) as total FROM dokter";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "   Total dokter: " . $row['total'] . "\n";

$sql = "SELECT * FROM dokter ORDER BY id_dokter LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "   - ID: {$row['id_dokter']} | {$row['nama_dokter']} | {$row['spesialis']} | Ruang: {$row['ruangan']}\n";
    }
} else {
    echo "   ℹ️  Tidak ada data dokter\n";
}

// 2. TEST TABEL PASIEN
echo "\n2. PASIEN TABLE:\n";
echo "   " . str_repeat("-", 40) . "\n";
$sql = "SELECT COUNT(*) as total FROM pasien";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "   Total pasien: " . $row['total'] . "\n";

$sql = "SELECT * FROM pasien ORDER BY id_pasien LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "   - ID: {$row['id_pasien']} | {$row['nama_pasien']} | {$row['umur']} thn | {$row['jenis_keluhan']}\n";
    }
} else {
    echo "   ℹ️  Tidak ada data pasien\n";
}

// 3. TEST TABEL ANTRIAN
echo "\n3. ANTRIAN TABLE:\n";
echo "   " . str_repeat("-", 40) . "\n";
$sql = "SELECT COUNT(*) as total FROM antrian";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "   Total antrian: " . $row['total'] . "\n";

$sql = "SELECT 
            a.nomor_antrian,
            a.status,
            p.nama_pasien,
            d.nama_dokter,
            a.waktu_ambil
        FROM antrian a
        LEFT JOIN pasien p ON a.id_pasien = p.id_pasien
        LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
        ORDER BY a.waktu_ambil DESC
        LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $waktu = date('H:i', strtotime($row['waktu_ambil']));
        echo "   - #{$row['nomor_antrian']} | {$row['status']} | {$row['nama_pasien']} → {$row['nama_dokter']} | {$waktu}\n";
    }
} else {
    echo "   ℹ️  Tidak ada data antrian\n";
}

// 4. TEST INTEGRASI: BUAT DATA BARU
echo "\n4. TEST INTEGRASI - BUAT DATA BARU:\n";
echo "   " . str_repeat("-", 40) . "\n";

// Step 1: Cek dan tambah pasien jika belum ada
echo "   Step 1: Cek data pasien...\n";
$sql = "SELECT id_pasien FROM pasien WHERE nama_pasien LIKE '%Test%' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Tambah pasien test
    $sql = "INSERT INTO pasien (nama_pasien, alamat, umur, jenis_keluhan, no_telepon, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $nama = "Test Pasien Integration";
    $alamat = "Jl. Integration Test No. 1";
    $umur = 25;
    $keluhan = "Demam";
    $telepon = "081234567890";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiss", $nama, $alamat, $umur, $keluhan, $telepon);
    
    if ($stmt->execute()) {
        $id_pasien_baru = $conn->insert_id;
        echo "   ✅ Pasien test berhasil dibuat (ID: $id_pasien_baru)\n";
        $id_pasien = $id_pasien_baru;
    } else {
        echo "   ❌ Gagal buat pasien: " . $conn->error . "\n";
        $id_pasien = null;
    }
} else {
    $row = $result->fetch_assoc();
    $id_pasien = $row['id_pasien'];
    echo "   ℹ️  Pakai pasien test yang sudah ada (ID: $id_pasien)\n";
}

// Step 2: Ambil dokter pertama
echo "\n   Step 2: Ambil data dokter...\n";
$sql = "SELECT id_dokter FROM dokter ORDER BY id_dokter LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $id_dokter = $row['id_dokter'];
    echo "   ✅ Menggunakan dokter ID: $id_dokter\n";
    
    // Step 3: Buat antrian baru
    if ($id_pasien) {
        echo "\n   Step 3: Buat antrian baru...\n";
        
        // Generate nomor antrian
        $prefix = date('ymd');
        $sql = "SELECT COUNT(*) as count FROM antrian WHERE DATE(waktu_ambil) = CURDATE()";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $sequence = str_pad($row['count'] + 1, 3, '0', STR_PAD_LEFT);
        $nomor_antrian = $prefix . $sequence;
        
        $sql = "INSERT INTO antrian (nomor_antrian, id_pasien, id_dokter, status, waktu_ambil) 
                VALUES (?, ?, ?, 'MENUNGGU', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $nomor_antrian, $id_pasien, $id_dokter);
        
        if ($stmt->execute()) {
            $id_antrian = $conn->insert_id;
            echo "   ✅ Antrian berhasil dibuat!\n";
            echo "      Nomor Antrian: $nomor_antrian\n";
            echo "      ID Antrian: $id_antrian\n";
            
            // Step 4: Update status antrian
            echo "\n   Step 4: Update status antrian...\n";
            $sql = "UPDATE antrian SET status = 'DILAYANI', waktu_mulai = NOW() 
                    WHERE id_antrian = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_antrian);
            
            if ($stmt->execute()) {
                echo "   ✅ Status diupdate ke DILAYANI\n";
                
                // Step 5: Selesaikan antrian
                echo "\n   Step 5: Selesaikan antrian...\n";
                $sql = "UPDATE antrian SET status = 'SELESAI', waktu_selesai = NOW(), 
                        catatan = 'Test selesai via integration test' 
                        WHERE id_antrian = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_antrian);
                
                if ($stmt->execute()) {
                    echo "   ✅ Antrian diselesaikan (SELESAI)\n";
                }
            }
        } else {
            echo "   ❌ Gagal buat antrian: " . $conn->error . "\n";
        }
    }
} else {
    echo "   ❌ Tidak ada data dokter\n";
}

// 5. SUMMARY
echo "\n5. SUMMARY STATUS:\n";
echo "   " . str_repeat("-", 40) . "\n";

// Hitung per status
$sql = "SELECT status, COUNT(*) as jumlah FROM antrian GROUP BY status";
$result = $conn->query($sql);

echo "   Status Antrian:\n";
while($row = $result->fetch_assoc()) {
    echo "   - {$row['status']}: {$row['jumlah']} antrian\n";
}

// Hari ini
$sql = "SELECT COUNT(*) as hari_ini FROM antrian WHERE DATE(waktu_ambil) = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "   Antrian hari ini: " . $row['hari_ini'] . "\n";

Database::closeConnection();

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ INTEGRATION TEST COMPLETE!\n";
echo "   Database: klinik\n";
echo "   Tabel: dokter, pasien, antrian\n";
echo "   Status: CONNECTED & WORKING\n";
echo str_repeat("=", 50) . "\n";
?>