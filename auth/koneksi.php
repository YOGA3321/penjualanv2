<?php
$host = 'localhost';
$db_user = 'root';
$db_pass = '';     // Ganti dengan password database Anda
$db_name = 'penjualanv2'; // Ganti dengan nama database Anda

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>