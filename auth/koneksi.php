<?php
date_default_timezone_set('Asia/Jakarta');
$whitelist = array('127.0.0.1', '::1', 'localhost', '192.168.0.192');

if (in_array($_SERVER['REMOTE_ADDR'], $whitelist) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false) {
    // LOCALHOST
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2';
    $base_path = '/penjualanv2'; 
} else {
    // HOSTING LIVE
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
$koneksi->query("SET time_zone = '+07:00'");

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base_path);