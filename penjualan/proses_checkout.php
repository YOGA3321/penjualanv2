<?php
// Header Anti-Crash
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
require_once dirname(__FILE__) . '/../vendor/autoload.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['plg_meja_id'])) { 
    ob_clean(); echo json_encode(['status'=>'error','message'=>'Sesi habis. Scan ulang QR.']); exit; 
}

$input = json_decode(file_get_contents('php://input'), true);
$meja_id = $_SESSION['plg_meja_id'];
$nama = $koneksi->real_escape_string($input['nama_pelanggan']);
$metode = $input['metode']; 
$items = $input['items'];

// --- VALIDASI DATA ---
// Pastikan total harga integer
$total_bayar = (int)$input['total_harga'];
$diskon = (int)($input['diskon'] ?? 0);
$kode_voucher = $input['kode_voucher'] ?? NULL;

// Validasi total tidak boleh 0 atau minus (Kecuali full diskon = 0 masih boleh, tapi minus jangan)
if ($total_bayar < 0) { 
    ob_clean(); echo json_encode(['status'=>'error','message'=>'Total harga tidak valid']); exit; 
}

// --- HITUNG POIN (User Login) ---
$poin_dapat = 0;
$user_id_db = NULL;
if (isset($_SESSION['user_id']) && $_SESSION['level'] == 'pelanggan') {
    $user_id_db = $_SESSION['user_id'];
    // Rumus: Setiap 10.000 dapat 1 Poin (Dihitung dari total sebelum atau sesudah diskon? Biasanya sesudah)
    $poin_dapat = floor($total_bayar / 10000);
}

// GENERATE ID
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$status_bayar = 'pending';
$status_pesanan = ($metode == 'tunai') ? 'menunggu_konfirmasi' : 'menunggu_bayar'; 
$snap_token = null;
$midtrans_order_id = null;

// --- MIDTRANS ---
if ($metode == 'midtrans') {
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    // Gunakan Total Bayar yang sudah didiskon!
    // Midtrans menolak jika gross_amount <= 0. Jadi jika gratis, harus tunai.
    if ($total_bayar <= 0) {
        ob_clean(); echo json_encode(['status'=>'error','message'=>'Total Rp 0 tidak bisa pakai QRIS. Pilih Tunai.']); exit; 
    }

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

// --- INSERT DATABASE (PERBAIKAN UTAMA) ---
// Perhatikan jumlah tanda tanya (?) ada 13 buah untuk mencocokkan bind_param
// Kolom: uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id, kode_voucher, diskon, poin_didapat, user_id
// Total kolom: 15. 
// Uang bayar & kembalian kita hardcode 0 di SQL, jadi sisa 13 parameter binding.

$sql = "INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id, kode_voucher, diskon, poin_didapat, user_id) VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $koneksi->prepare($sql);

if (!$stmt) {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Prepare Failed: ' . $koneksi->error]); exit;
}

// Bind Param: 13 Variabel
// s = string, i = integer, d = double
$stmt->bind_param("sissssssssdii", 
    $uuid,                  // s
    $meja_id,               // i
    $nama,                  // s
    $total_bayar,           // s (bisa i/d juga, tapi s aman)
    $status_bayar,          // s
    $metode,                // s
    $status_pesanan,        // s
    $snap_token,            // s
    $midtrans_order_id,     // s
    $kode_voucher,          // s
    $diskon,                // d
    $poin_dapat,            // i
    $user_id_db             // i
);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stok = $koneksi->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
    
    foreach ($items as $item) {
        $qty = (int)($item['qty'] ?? $item['quantity'] ?? 1);
        $harga = (int)($item['harga'] ?? $item['price'] ?? 0);
        $sub = $harga * $qty;
        
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $qty, $harga, $sub);
        $stmt_detail->execute();
        
        $stmt_stok->bind_param("ii", $qty, $item['id']);
        $stmt_stok->execute();
    }
    
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");
    
    // Kurangi Stok Voucher
    if($kode_voucher) {
        $koneksi->query("UPDATE vouchers SET stok = stok - 1 WHERE kode = '$kode_voucher'");
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'uuid' => $uuid, 'snap_token' => $snap_token]);
} else {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>