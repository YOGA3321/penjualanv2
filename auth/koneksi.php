<?php
// Set Timezone PHP
date_default_timezone_set('Asia/Jakarta');

// --- 1. DETEKSI ENVIRONMENT (LOKAL VS LIVE) ---
// Daftar IP/Host yang dianggap Localhost
$whitelist = array('127.0.0.1', '::1', 'localhost');

// Cek apakah server saat ini adalah Localhost
$is_localhost = false;
if (in_array($_SERVER['REMOTE_ADDR'], $whitelist) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false) {
    $is_localhost = true;
}

if ($is_localhost) {
    // === SETTING LOCALHOST (LARAGON/XAMPP) ===
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'penjualan2';
    
    // Sesuaikan dengan nama folder di htdocs/www Anda
    // Contoh: localhost/penjualanv2 -> isi '/penjualanv2'
    $base_path = '/penjualanv2'; 

} else {
    // === SETTING HOSTING (LIVE) ===
    $host = 'localhost';
    $db_user = 'u116133173_penjualan2'; // User Database Hosting
    $db_pass = '@Yogabd46';             // Password Database Hosting
    $db_name = 'u116133173_penjualan2'; // Nama Database Hosting
    
    // Jika domain langsung (misal: lopyta.com), kosongkan string ini
    // Jika di subfolder (misal: lopyta.com/app), isi '/app'
    $base_path = ''; 
}

// --- 2. KONEKSI DATABASE ---
$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    // Tampilkan error hanya jika di localhost demi keamanan
    if ($is_localhost) {
        die("Koneksi Database Gagal: " . mysqli_connect_error());
    } else {
        die("Koneksi Gagal. Silakan hubungi admin.");
    }
}

// Sinkronisasi Timezone MySQL
$koneksi->query("SET time_zone = '+07:00'");

// --- 3. DEFINISI BASE_URL (PENTING UNTUK GOOGLE LOGIN) ---
// Deteksi HTTPS (termasuk support Cloudflare/Proxy)
$protocol = 'http://';
if (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) {
    $protocol = 'https://';
}

// Gabungkan menjadi URL lengkap
$base_url = $protocol . $_SERVER['HTTP_HOST'] . $base_path;

// Definisikan Constant BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
?>