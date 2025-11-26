<?php
// $host = 'localhost';
// $db_user = 'root';
// $db_pass = '';
// $db_name = 'penjualan2';


$host = 'localhost';
$db_user = 'u116133173_penjualan2';
$db_pass = '@Yogabd46';
$db_name = 'u116133173_penjualan2';

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$is_localhost = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1');

// Jika localhost pakai folder project, jika live pakai kosong (root)
$base_path = $is_localhost ? '/penjualanv2' : ''; 
define('BASE_URL', "https://" . $_SERVER['HTTP_HOST'] . $base_path);
?>