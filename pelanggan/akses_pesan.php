<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['plg_meja_id'] = $_POST['meja_id'];
    $_SESSION['plg_no_meja'] = $_POST['no_meja'];
    $_SESSION['plg_cabang_id'] = $_POST['cabang_id'];
    $_SESSION['plg_nama_cabang'] = $_POST['nama_cabang'];
    $_SESSION['force_reset_cart'] = true; // Reset keranjang lama
    
    header("Location: ../penjualan/index");
} else {
    header("Location: index");
}
?>