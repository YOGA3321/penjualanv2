<?php
// DETEKSI OTOMATIS: Apakah di Localhost atau Live Server?
$whitelist = array('127.0.0.1', '::1', 'localhost', '192.168.0.192');

if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2';
    $base_path = '/penjualanv2'; 
} else {
    $host = 'localhost';
    $db_user = 'u116133173_penjualan2';
    $db_pass = '@Yogabd46';
    $db_name = 'u116133173_penjualan2';
    $base_path = ''; 
}

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi Error: " . mysqli_connect_error());
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base_path);