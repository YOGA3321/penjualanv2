<?php
date_default_timezone_set('Asia/Jakarta');

$host_url = $_SERVER['HTTP_HOST']; 
if (strpos($host_url, 'localhost') !== false || strpos($host_url, '127.0.0.1') !== false || strpos($host_url, '192.168.') !== false) {
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2';
    $base_path = '/penjualanv2';
} else {
    // --- SETTINGAN HOSTING (LIVE) ---
    $host = 'localhost';
    $db_user = 'u116133173_penjualan2';
    $db_pass = '@Yogabd46';
    $db_name = 'u116133173_penjualan2';
    $base_path = ''; 
}

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base_path);