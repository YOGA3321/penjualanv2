<?php
// 1. Mulai Sesi (Wajib paling atas)
session_start();

// 2. Matikan Error Display HTML (Agar JSON bersih)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 3. Handler Fatal Error (Agar server tidak diam saat crash)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $error['message']]);
        exit;
    }
});

// 4. Buffer Output
ob_start();

require_once '../../auth/koneksi.php';

// [FIX] Pastikan path autoload benar
$autoload_path = dirname(__FILE__) . '/../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Library Midtrans tidak ditemukan. Jalankan composer install.']);
    exit;
}
require_once $autoload_path;

header('Content-Type: application/json');

// Cek Login Kasir
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis (Unauthorized). Silakan login ulang.']); 
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) { ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Data JSON invalid']); exit; }

$meja_id = $data['meja_id'];
$nama_pelanggan = $data['nama_pelanggan'];
$total_harga = (int)$data['total_harga'];
$items = $data['items'];
$metode = $data['metode']; 
$uang_bayar = $data['uang_bayar'] ?? 0;
$kembalian = $data['kembalian'] ?? 0;

// ==========================================================
// LOGIKA RESUME (Agar tidak dobel order saat klik tombol lagi)
// ==========================================================
if ($metode == 'midtrans') {
    // Cek apakah meja ini punya transaksi PENDING Midtrans?
    $cek_pending = $koneksi->query("SELECT uuid, snap_token FROM transaksi 
                                    WHERE meja_id = '$meja_id' 
                                    AND status_pembayaran = 'pending' 
                                    AND metode_pembayaran = 'midtrans'
                                    ORDER BY id DESC LIMIT 1");
                                    
    if ($cek_pending->num_rows > 0) {
        $existing = $cek_pending->fetch_assoc();
        ob_clean();
        // Kembalikan Token Lama
        echo json_encode([
            'status' => 'success', 
            'uuid' => $existing['uuid'], 
            'snap_token' => $existing['snap_token'],
            'info' => 'Melanjutkan transaksi sebelumnya'
        ]);
        exit; // Stop, jangan insert baru
    }
}

// ==========================================================
// LOGIKA TRANSAKSI BARU
// ==========================================================

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$snap_token = null;
$midtrans_order_id = null;

if ($metode == 'midtrans') {
    // Konfigurasi Server Key (Pastikan Sama dengan Akun Midtrans Anda)
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $short_uuid = substr($uuid, 0, 8);
    // Prefix RESTO wajib ada untuk Router Cloudflare
    $midtrans_order_id = "RESTO-" . $short_uuid . "-" . time();

    $params = [
        'transaction_details' => ['order_id' => $midtrans_order_id, 'gross_amount' => $total_harga],
        'customer_details' => [ 'first_name' => $nama_pelanggan ],
        'custom_field1' => $uuid
    ];

    try {
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]); exit;
    }
}

// Status Awal
$status_bayar = ($metode == 'tunai') ? 'settlement' : 'pending';
$status_pesanan = ($metode == 'tunai') ? 'diproses' : 'menunggu_bayar'; 

// Insert Database (Pastikan kolom midtrans_id sudah dibuat di DB)
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Bind Param (11 parameter)
$stmt->bind_param("sisdddsssss", $uuid, $meja_id, $nama_pelanggan, $total_harga, $uang_bayar, $kembalian, $status_bayar, $metode, $status_pesanan, $snap_token, $midtrans_order_id);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $subtotal = $item['harga'] * $item['qty'];
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $item['qty'], $item['harga'], $subtotal);
        $stmt_detail->execute();
    }
    
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");

    ob_clean();
    echo json_encode([
        'status' => 'success', 
        'uuid' => $uuid, 
        'snap_token' => $snap_token
    ]);
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
}
?>