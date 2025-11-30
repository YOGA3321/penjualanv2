<?php
session_start();
require_once '../../auth/koneksi.php';

// [FIX 1] AKTIFKAN BARIS INI (Hapus tanda //)
require_once dirname(__FILE__) . '/../../vendor/autoload.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) { echo json_encode(['status' => 'error', 'message' => 'Data invalid']); exit; }

$meja_id = $data['meja_id'];
$nama_pelanggan = $data['nama_pelanggan'];
$total_harga = (int)$data['total_harga'];
$items = $data['items'];
$metode = $data['metode']; // 'tunai' atau 'midtrans'
$uang_bayar = $data['uang_bayar'] ?? 0;
$kembalian = $data['kembalian'] ?? 0;

// Generate UUID
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$snap_token = null;
$midtrans_order_id = null;

// --- LOGIKA MIDTRANS (UNTUK KASIR) ---
if ($metode == 'midtrans') {
    // [FIX 2] Pastikan Server Key ini BENAR (Pasangan dari Client Key di frontend)
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $short_uuid = substr($uuid, 0, 8);
    // Tambahkan Prefix RESTO agar router notifikasi bisa membacanya
    $midtrans_order_id = "RESTO-" . $short_uuid . "-" . time();

    $params = [
        'transaction_details' => [
            'order_id' => $midtrans_order_id,
            'gross_amount' => $total_harga,
        ],
        'customer_details' => [ 'first_name' => $nama_pelanggan ],
        // Kirim UUID asli database agar saat notifikasi balik kita bisa update status
        'custom_field1' => $uuid 
    ];

    try {
        // Buat Token Snap ASLI
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]); exit;
    }
}

// Status Awal
// Jika tunai -> LUNAS (settlement). Jika Midtrans -> PENDING.
$status_bayar = ($metode == 'tunai') ? 'settlement' : 'pending';
// Jika tunai -> MASUK DAPUR (diproses). Jika Midtrans -> MENUNGGU BAYAR.
$status_pesanan = ($metode == 'tunai') ? 'diproses' : 'menunggu_bayar'; 

// Insert Database
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisdddssss", $uuid, $meja_id, $nama_pelanggan, $total_harga, $uang_bayar, $kembalian, $status_bayar, $metode, $status_pesanan, $snap_token);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    
    // Insert Detail
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $subtotal = $item['harga'] * $item['qty'];
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $item['qty'], $item['harga'], $subtotal);
        $stmt_detail->execute();
    }
    
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");

    echo json_encode([
        'status' => 'success', 
        'uuid' => $uuid, 
        'snap_token' => $snap_token // Token ini yang ditunggu Javascript
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
}
?>