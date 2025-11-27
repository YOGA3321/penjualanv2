<?php
// auth/koneksi.php

// DETEKSI OTOMATIS: Apakah di Localhost atau Live Server?
$whitelist = array('127.0.0.1', '::1', 'localhost');

if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    // --- KONEKSI LOKAL (Laptop Anda) ---
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2'; // Nama DB Lokal
    $base_path = '/penjualanv2'; // Folder di htdocs
} else {
    // --- KONEKSI LIVE (Hostinger) ---
    $host = 'localhost';
    $db_user = 'u116133173_penjualan2';
    $db_pass = '@Yogabd46';
    $db_name = 'u116133173_penjualan2';
    $base_path = ''; // Di hosting biasanya langsung root domain
}

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi Error: " . mysqli_connect_error()); // Ini akan bantu tampilkan error di layar
}

define('BASE_URL', "https://" . $_SERVER['HTTP_HOST'] . $base_path);
?>