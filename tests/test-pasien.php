<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/models/Pasien.php';

$db = (new Database())->getConnection();
$pasien = new Pasien($db);

/* TEST CREATE */
$pasien->nama_pasien   = "Pasien Test";
$pasien->umur          = 20;
$pasien->alamat        = "Bandung";
$pasien->jenis_keluhan = "Flu";

$pasien->create();

/* TEST GET */
print_r($pasien->getAll());
