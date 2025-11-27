<?php
session_start();
require_once '../auth/koneksi.php';
header('Content-Type: application/json');

// Cek Sesi Meja
if (!isset($_SESSION['plg_meja_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis, scan ulang QR']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$meja_id = $_SESSION['plg_meja_id'];
$nama = $koneksi->real_escape_string($input['nama_pelanggan']);
$total = $input['total_harga'];
$metode = $input['metode'];
$items = $input['items'];

// Generate UUID
$uuid = uniqid() . rand(100, 999); // Simple unique ID

// Status Awal
// Jika Tunai -> Menunggu Konfirmasi Kasir -> Baru ke Dapur
// Jika Midtrans -> Pending (Nunggu Bayar) -> Setelah Bayar -> Dapur
$status_bayar = 'pending';
$status_pesanan = ($metode == 'tunai') ? 'menunggu_konfirmasi' : 'menunggu_bayar'; 

// Insert Header Transaksi
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, status_pembayaran, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisssss", $uuid, $meja_id, $nama, $total, $status_bayar, $metode, $status_pesanan);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    
    // Insert Detail
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $subtotal = $item['price'] * $item['qty'];
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $item['qty'], $item['price'], $subtotal);
        $stmt_detail->execute();
    }
    
    // Update Status Meja
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");

    echo json_encode(['status' => 'success', 'uuid' => $uuid]);
} else {
    echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
}
?>