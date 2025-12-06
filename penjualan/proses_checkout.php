<?php
// ... (Header Anti Crash) ...
ini_set('display_errors', 0);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500); header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $error['message']]); exit;
    }
});

ob_start();
session_start();
require_once '../auth/koneksi.php';
// Sesuaikan path vendor jika perlu (pastikan path ini benar di hosting Anda)
require_once dirname(__FILE__) . '/../vendor/autoload.php'; 

header('Content-Type: application/json');

// Cek Sesi Meja
if (!isset($_SESSION['plg_meja_id'])) { 
    ob_clean(); 
    echo json_encode(['status'=>'error','message'=>'Sesi meja habis, silahkan scan ulang QR code.']); 
    exit; 
}

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];

// ==========================================
// [FIX] PENGAMAN KERANJANG KOSONG
// ==========================================
if (empty($items) || count($items) <= 0) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => 'Keranjang belanja kosong! Tidak ada item yang bisa dipesan.'
    ]);
    exit;
}

$meja_id = $_SESSION['plg_meja_id'];
$nama = $koneksi->real_escape_string($input['nama_pelanggan']);
$metode = $input['metode']; 

// Data Keuangan
$total_bayar = (int)$input['total_harga']; 
$diskon = (int)($input['diskon'] ?? 0);
$kode_voucher = $input['kode_voucher'] ?? NULL;

// --- HITUNG POIN (User Login) ---
$poin_dapat = 0;
$user_id_db = NULL;
$is_logged_in = false;

if (isset($_SESSION['user_id']) && $_SESSION['level'] == 'pelanggan') {
    $user_id_db = $_SESSION['user_id'];
    $is_logged_in = true;
    // Rumus: Setiap 10.000 dapat 1 Poin
    $poin_dapat = floor($total_bayar / 10000);
}

// GENERATE ID
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$status_bayar = 'pending';
$status_pesanan = ($metode == 'tunai') ? 'menunggu_konfirmasi' : 'menunggu_bayar'; 
$snap_token = null;
$midtrans_order_id = null;

// --- MIDTRANS LOGIC ---
if ($metode == 'midtrans') {
    // Pastikan Server Key Benar
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $kode_unik = date('ymd') . rand(100, 999); 
    $midtrans_order_id = "PENJUALAN-" . $kode_unik; 

    $params = [
        'transaction_details' => ['order_id' => $midtrans_order_id, 'gross_amount' => $total_bayar],
        'customer_details' => [ 'first_name' => $nama ],
        'custom_field1' => $uuid,
        'expiry' => [ 'start_time' => date("Y-m-d H:i:s O"), 'unit' => 'minutes', 'duration' => 30 ]
    ];

    try {
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]); exit;
    }
}

// --- INSERT TRANSAKSI ---
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id, kode_voucher, diskon, poin_didapat, user_id) VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sissssssssdii", $uuid, $meja_id, $nama, $total_bayar, $status_bayar, $metode, $status_pesanan, $snap_token, $midtrans_order_id, $kode_voucher, $diskon, $poin_dapat, $user_id_db);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal, catatan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_stok = $koneksi->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
    
    foreach ($items as $item) {
        // [FIX] Normalisasi Key dari Javascript
        // Kadang JS kirim 'quantity', kadang 'qty'
        $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
        
        // Kadang JS kirim 'price', kadang 'harga'
        $harga = isset($item['price']) ? $item['price'] : (isset($item['harga']) ? $item['harga'] : 0);
        
        // Pastikan angka
        $qty = (int)$qty;
        $harga = (int)$harga;

        $sub = $harga * $qty;
        
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $qty, $harga, $sub);
        $stmt_detail->execute();
        
        $stmt_stok->bind_param("ii", $qty, $item['id']);
        $stmt_stok->execute();
    }

    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");
    
    // UPDATE STOK VOUCHER
    if($kode_voucher) {
        $koneksi->query("UPDATE vouchers SET stok = stok - 1 WHERE kode = '$kode_voucher'");
    }

    // --- RESPONSE UNTUK FRONTEND ---
    ob_clean();
    echo json_encode([
        'status' => 'success', 
        'uuid' => $uuid, 
        'snap_token' => $snap_token,
        'is_logged_in' => $is_logged_in 
    ]);
} else {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
}
?>