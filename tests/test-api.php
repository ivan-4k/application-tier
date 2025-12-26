<?php
// API Test Script
$baseUrl = 'http://localhost/app-klinik/public';

echo "Testing Klinik Antrian API\n";
echo "==========================\n\n";

// Test 1: Health Check
echo "1. Testing Health Check:\n";
$response = file_get_contents($baseUrl . '/health');
echo "Response: " . $response . "\n\n";

// Test 2: Get Doctors
echo "2. Testing Get Doctors:\n";
$response = file_get_contents($baseUrl . '/dokter');
$data = json_decode($response, true);
echo "Total Doctors: " . ($data['data']['total'] ?? 0) . "\n";
echo "Success: " . ($data['success'] ? '✅' : '❌') . "\n\n";

// Test 3: Create Queue (using cURL)
echo "3. Testing Create Queue:\n";
$postData = json_encode([
    'nama' => 'Test Patient',
    'umur' => 30,
    'alamat' => 'Jl. Test No. 123',
    'keluhan' => 'Demam',
    'dokter_id' => 1
]);

$ch = curl_init($baseUrl . '/antrian');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "Queue Created: " . ($data['success'] ? '✅' : '❌') . "\n";
if ($data['success']) {
    echo "Queue Number: " . $data['data']['nomor'] . "\n";
}
echo "\n";

// Test 4: Get Statistics
echo "4. Testing Get Statistics:\n";
$response = file_get_contents($baseUrl . '/antrian/statistik');
$data = json_decode($response, true);
echo "Statistics: \n";
print_r($data['data']);
?>