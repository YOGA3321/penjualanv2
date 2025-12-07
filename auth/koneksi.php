<?php
// Set Timezone PHP
date_default_timezone_set('Asia/Jakarta');

// --- 1. DETEKSI ENVIRONMENT (LOKAL VS LIVE) ---
$whitelist = array('127.0.0.1', '::1', 'localhost');
$is_localhost = false;
if (in_array($_SERVER['REMOTE_ADDR'], $whitelist) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false) {
    $is_localhost = true;
}

if ($is_localhost) {
    // === SETTING LOCALHOST ===
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2';
    $base_path = '/penjualanv2'; 
    $protocol = 'http://';
} else {
    // === SETTING HOSTING (LIVE) ===
    $host = 'localhost';
    $db_user = 'u116133173_penjualan2'; 
    $db_pass = '@Yogabd46';             
    $db_name = 'u116133173_penjualan2'; 
    $base_path = '';
    $protocol = 'https://';
}

// --- 2. KONEKSI DATABASE ---
$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

$koneksi->query("SET time_zone = '+07:00'");

// --- 3. DEFINISI BASE_URL ---
// PENTING: Gunakan HTTP_HOST yang bersih (tanpa www jika di Google Console tanpa www)
$http_host = $_SERVER['HTTP_HOST'];

// [FIX] Paksa URL sesuai Google Console jika di hosting
if (!$is_localhost) {
    // Ganti ini dengan domain persis yang ada di Google Console
    // Contoh: sale.lopyta.com
    $fixed_domain = 'sale.lopyta.com'; 
    if (strpos($http_host, $fixed_domain) !== false) {
        $http_host = $fixed_domain; 
    }
}

$base_url = $protocol . $http_host . $base_path;

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
?>