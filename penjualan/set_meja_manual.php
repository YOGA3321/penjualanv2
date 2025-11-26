<?php
session_start();
require_once '../auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['meja_id']) && !empty($_POST['meja_id'])) {
    $meja_id = $_POST['meja_id'];
    
    // Ambil info detail meja & cabang
    $query = "SELECT meja.*, cabang.nama_cabang, cabang.id as id_cabang 
              FROM meja 
              JOIN cabang ON meja.cabang_id = cabang.id 
              WHERE meja.id = '$meja_id'";
              
    $result = $koneksi->query($query);
    $info = $result->fetch_assoc();
                             
    if ($info) {
        // SET SESI PELANGGAN (Pura-pura jadi pelanggan di meja itu)
        $_SESSION['plg_meja_id'] = $info['id'];
        $_SESSION['plg_no_meja'] = $info['nomor_meja'];
        $_SESSION['plg_cabang_id'] = $info['id_cabang'];
        $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
        
        // Sukses -> Masuk ke Menu
        header("Location: index.php");
        exit;
    } else {
        // Data meja error/tidak ditemukan
        echo "<script>alert('Data meja tidak valid!'); window.history.back();</script>";
    }
} else {
    // Jika form dikirim kosong
    echo "<script>alert('Silakan pilih meja terlebih dahulu!'); window.history.back();</script>";
}
?>