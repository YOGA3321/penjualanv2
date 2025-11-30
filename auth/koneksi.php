<?php
// DETEKSI OTOMATIS: Apakah di Localhost atau Live Server?
$whitelist = array('127.0.0.1', '::1', 'localhost');

// Cek apakah IP Pengunjung ada di whitelist localhost
if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    // --- KONEKSI LOKAL (Laptop) ---
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2';
    $base_path = '/penjualanv2'; // Folder project di htdocs/www
} else {
    // --- KONEKSI LIVE (Hosting) ---
    $host = 'localhost';
    $db_user = 'u116133173_penjualan2';
    $db_pass = '@Yogabd46';
    $db_name = 'u116133173_penjualan2';
    $base_path = ''; // Biasanya kosong jika di subdomain/root
}

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi Error: " . mysqli_connect_error());
}

// --- DETEKSI PROTOKOL (HTTP vs HTTPS) ---
// Cek apakah server menggunakan HTTPS atau tidak
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";

// Gabungkan Protokol + Host + Folder
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base_path);
?>