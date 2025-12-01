<?php
// Anti-Crash Header
ini_set('display_errors', 0);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $error['message']]);
        exit;
    }
});

ob_start();
session_start();
require_once '../auth/koneksi.php';

// Load Library Midtrans
$autoload_path = dirname(__FILE__) . '/../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Library Midtrans hilang']); exit;
}
require_once $autoload_path;

header('Content-Type: application/json');

// Cek Sesi
if (!isset($_SESSION['plg_meja_id'])) {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Sesi habis, scan ulang QR']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Data invalid']); exit; }

$meja_id = $_SESSION['plg_meja_id'];
$nama = $koneksi->real_escape_string($input['nama_pelanggan']);
$total = (int)$input['total_harga'];
$metode = $input['metode']; 
$items = $input['items'];

// ============================================================
// [BARU] CEK TRANSAKSI PENDING (ANTI DOBEL ORDER)
// ============================================================
if ($metode == 'midtrans') {
    // Cari transaksi di meja ini, status pending, metode midtrans, dan total harga SAMA
    // Kita cek total harga juga untuk memastikan ini bukan order tambahan yg berbeda menu
    $cek_pending = $koneksi->query("SELECT uuid, snap_token FROM transaksi 
                                    WHERE meja_id = '$meja_id' 
                                    AND status_pembayaran = 'pending' 
                                    AND metode_pembayaran = 'midtrans'
                                    AND total_harga = '$total' 
                                    ORDER BY id DESC LIMIT 1");
                                    
    if ($cek_pending->num_rows > 0) {
        $existing = $cek_pending->fetch_assoc();
        ob_clean();
        echo json_encode([
            'status' => 'success', 
            'uuid' => $existing['uuid'], 
            'snap_token' => $existing['snap_token'],
            'info' => 'Resume transaksi sebelumnya'
        ]);
        exit; // STOP DISINI
    }
}

// ============================================================
// BUAT BARU
// ============================================================

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

$status_bayar = 'pending';
$status_pesanan = ($metode == 'tunai') ? 'menunggu_konfirmasi' : 'menunggu_bayar'; 
$snap_token = null;
$midtrans_order_id = null;

if ($metode == 'midtrans') {
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $short_uuid = substr($uuid, 0, 8); 
    $midtrans_order_id = "RESTO-" . $short_uuid . "-" . time(); 

    $params = [
        'transaction_details' => ['order_id' => $midtrans_order_id, 'gross_amount' => $total],
        'customer_details' => [ 'first_name' => $nama ],
        'custom_field1' => $uuid 
    ];

    try {
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]); exit;
    }
}

// Insert Database
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisssssss", $uuid, $meja_id, $nama, $total, $status_bayar, $metode, $status_pesanan, $snap_token, $midtrans_order_id);

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

    ob_clean();
    echo json_encode(['status' => 'success', 'uuid' => $uuid, 'snap_token' => $snap_token]);
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
}
?>