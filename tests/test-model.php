<?php
require_once "app/config/Database.php";
require_once "app/models/Dokter.php";

$db = (new Database())->getConnection();
$dokter = new Dokter($db);

$data = $dokter->getAll();
print_r($data->fetchAll(PDO::FETCH_ASSOC));
