<?php
session_start();
require_once '../auth/koneksi.php';

// [FIX] AKTIFKAN LIBRARY MIDTRANS
require_once dirname(__FILE__) . '/../vendor/autoload.php'; 

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

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$status_bayar = 'pending';
$status_pesanan = ($metode == 'tunai') ? 'menunggu_konfirmasi' : 'menunggu_bayar'; 
$snap_token = null;

// --- LOGIKA MIDTRANS ---
if ($metode == 'midtrans') {
    // Pastikan Server Key SAMA dengan yang di admin
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $short_uuid = substr($uuid, 0, 8); 
    $midtrans_order_id = "RESTO-" . $short_uuid . "-" . time(); 

    $params = [
        'transaction_details' => [
            'order_id' => $midtrans_order_id,
            'gross_amount' => (int)$total,
        ],
        'customer_details' => [
            'first_name' => $nama,
        ],
        'custom_field1' => $uuid 
    ];

    try {
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]); exit;
    }
}

// ... (Sisa kode insert database sama seperti file asli Anda) ...
// Insert Header Transaksi
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, status_pembayaran, metode_pembayaran, status_pesanan, snap_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sissssss", $uuid, $meja_id, $nama, $total, $status_bayar, $metode, $status_pesanan, $snap_token);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $qty = $item['quantity']; 
        $harga = $item['price'];
        $subtotal = $harga * $qty;
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $qty, $harga, $subtotal);
        $stmt_detail->execute();
    }
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");

    echo json_encode([
        'status' => 'success', 
        'uuid' => $uuid,
        'snap_token' => $snap_token
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
}
?>