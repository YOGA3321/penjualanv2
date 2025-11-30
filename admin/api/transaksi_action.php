<?php
session_start();
require_once '../../auth/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? ''; // Bisa ID Transaksi atau ID Meja

if ($action == 'konfirmasi_bayar') {
    // Kasir menerima uang tunai dari pelanggan yg order mandiri
    $uang = $_POST['uang_bayar'];
    $kembali = $_POST['kembalian'];
    
    $query = "UPDATE transaksi SET 
              status_pembayaran = 'settlement', 
              status_pesanan = 'diproses', 
              uang_bayar = '$uang', 
              kembalian = '$kembali' 
              WHERE id = '$id'";
              
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Pembayaran Dikonfirmasi! Masuk ke Dapur.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
} 

elseif ($action == 'selesai_masak') {
    // Dapur menyelesaikan pesanan
    $query = "UPDATE transaksi SET status_pesanan = 'siap_saji' WHERE id = '$id'";
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Pesanan Siap Disajikan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
}

elseif ($action == 'kosongkan_meja') {
    // Pelayan membersihkan meja (Update status meja & selesaikan transaksi terkait)
    // 1. Update Transaksi terakhir di meja itu jadi 'selesai' (jika belum)
    $koneksi->query("UPDATE transaksi SET status_pesanan = 'selesai' WHERE meja_id = '$id' AND status_pesanan != 'selesai'");
    
    // 2. Set Meja jadi Kosong
    $query = "UPDATE meja SET status = 'kosong' WHERE id = '$id'";
    
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Meja berhasil dikosongkan.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
}

else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
}
?>