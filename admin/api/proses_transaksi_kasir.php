<?php
session_start();
require_once '../../auth/koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 1. Ambil Data JSON dari JS
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

$meja_id = $data['meja_id'];
$nama_pelanggan = $data['nama_pelanggan'];
$total_harga = $data['total_harga'];
$items = $data['items'];
$metode = $data['metode']; // 'tunai' atau 'midtrans'

// 2. Generate UUID
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// 3. Status Awal
$status_bayar = ($metode == 'tunai') ? 'settlement' : 'pending';
$status_pesanan = 'diproses'; // Langsung diproses dapur

// 4. Insert Transaksi Header
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, status_pembayaran, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisssss", $uuid, $meja_id, $nama_pelanggan, $total_harga, $status_bayar, $metode, $status_pesanan);

if ($stmt->execute()) {
    $transaksi_id = $koneksi->insert_id;
    
    // 5. Insert Detail Item
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $subtotal = $item['harga'] * $item['qty'];
        $stmt_detail->bind_param("iiidd", $transaksi_id, $item['id'], $item['qty'], $item['harga'], $subtotal);
        $stmt_detail->execute();
    }
    
    // 6. Update Status Meja -> Terisi
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");

    echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil disimpan!', 'uuid' => $uuid]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan transaksi: ' . $stmt->error]);
}
?>