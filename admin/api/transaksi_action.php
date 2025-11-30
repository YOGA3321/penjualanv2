<?php
session_start();
require_once '../../auth/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';

if ($action == 'konfirmasi_bayar') {
    $uang = $_POST['uang_bayar'];
    $kembali = $_POST['kembalian'];
    
    // [FIX LOGIC]
    // 1. Ubah status_pembayaran -> 'settlement' (Lunas)
    // 2. Ubah status_pesanan -> 'diproses' (Agar muncul di API Dapur)
    
    $query = "UPDATE transaksi SET 
              status_pembayaran = 'settlement', 
              status_pesanan = 'diproses', 
              uang_bayar = '$uang', 
              kembalian = '$kembali' 
              WHERE id = '$id'";
              
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Lunas! Pesanan dikirim ke Dapur.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
} 

elseif ($action == 'selesai_masak') {
    // Dari Dapur -> Siap Saji (Hilang dari layar dapur)
    $query = "UPDATE transaksi SET status_pesanan = 'siap_saji' WHERE id = '$id'";
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Pesanan Siap Disajikan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
}

elseif ($action == 'kosongkan_meja') {
    // 1. Selesaikan semua pesanan di meja itu
    $koneksi->query("UPDATE transaksi SET status_pesanan = 'selesai' WHERE meja_id = '$id' AND status_pesanan != 'selesai'");
    
    // 2. Set Meja jadi Kosong (Agar bisa discan pelanggan baru)
    $query = "UPDATE meja SET status = 'kosong' WHERE id = '$id'";
    
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Meja Kosong & Siap Pakai.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
}
?>