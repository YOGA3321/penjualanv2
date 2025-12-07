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
$items_client = $input['items'];
$kode_voucher = $input['kode_voucher'] ?? NULL;

// Validasi Keranjang Kosong
if (empty($items_client)) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Keranjang kosong']); exit; }

// =======================================================================
// 1. HITUNG ULANG HARGA & PROMO (SERVER SIDE VALIDATION)
// =======================================================================
$total_real = 0;
$fixed_items = []; 

foreach ($items_client as $item) {
    $id_menu = (int)$item['id'];
    $qty = (int)$item['qty'];
    
    // Ambil Harga Terbaru dari DB
    $q_cek = $koneksi->query("SELECT harga, harga_promo, is_promo, stok, nama_menu FROM menu WHERE id = '$id_menu' AND is_active = 1");
    $db_menu = $q_cek->fetch_assoc();
    
    if(!$db_menu) {
        ob_clean(); echo json_encode(['status'=>'error', 'message'=>"Menu '{$item['nama']}' tidak tersedia/dihapus."]); exit;
    }
    
    // Cek Stok
    if ($qty > $db_menu['stok']) {
        ob_clean(); echo json_encode(['status'=>'error', 'message'=>"Stok '{$db_menu['nama_menu']}' tersisa {$db_menu['stok']}."]); exit;
    }

    // Tentukan Harga Fix (Promo atau Normal)
    $harga_fix = $db_menu['harga'];
    if ($db_menu['is_promo'] == 1 && $db_menu['harga_promo'] > 0) {
        $harga_fix = $db_menu['harga_promo'];
    }
    
    // Simpan ke array baru untuk Insert nanti
    $subtotal = $harga_fix * $qty;
    $total_real += $subtotal;
    
    $fixed_items[] = [
        'id' => $id_menu,
        'qty' => $qty,
        'harga_satuan' => $harga_fix,
        'subtotal' => $subtotal
    ];
}

// =======================================================================
// 2. HITUNG ULANG DISKON VOUCHER
// =======================================================================
$diskon_real = 0;
$today = date('Y-m-d');

if ($kode_voucher) {
    $q_v = $koneksi->query("SELECT * FROM vouchers WHERE kode = '$kode_voucher' AND stok > 0 AND berlaku_sampai >= '$today'");
    $v = $q_v->fetch_assoc();
    
    if ($v) {
        if ($total_real >= $v['min_belanja']) {
            if ($v['tipe'] == 'fixed') {
                $diskon_real = $v['nilai'];
            } else {
                $diskon_real = ($total_real * $v['nilai']) / 100;
            }
        }
    }
}

// Finalisasi Total Bayar
if ($diskon_real > $total_real) $diskon_real = $total_real;
$total_akhir_server = $total_real - $diskon_real;

// Validasi Midtrans (Min Rp 1)
if ($total_akhir_server <= 0 && $metode == 'midtrans') {
    ob_clean(); echo json_encode(['status'=>'error','message'=>'Total Rp 0 tidak bisa pakai QRIS. Gunakan Tunai.']); exit;
}

// =======================================================================
// 3. PROSES INSERT (Database)
// =======================================================================

// Hitung Poin
$poin_dapat = 0;
$user_id_db = NULL;
if (isset($_SESSION['user_id']) && $_SESSION['level'] == 'pelanggan') {
    $user_id_db = $_SESSION['user_id'];
    $poin_dapat = floor($total_akhir_server / 10000);
}

// Generate UUID & Params
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$status_pesanan = ($metode == 'tunai') ? 'menunggu_konfirmasi' : 'menunggu_bayar'; 
$snap_token = null;
$midtrans_order_id = null;

if ($metode == 'midtrans') {
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; // Pastikan Key Benar
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    // --- PERBAIKAN DI SINI ---
    // Prefix diubah menjadi 'RESTO-' agar ditangkap Cloudflare Worker
    $kode_unik = date('ymd') . rand(100, 999); 
    $midtrans_order_id = "RESTO-" . $kode_unik; 
    // -------------------------

    $params = [
        'transaction_details' => ['order_id' => $midtrans_order_id, 'gross_amount' => $total_akhir_server],
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

// Insert Transaksi
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id, kode_voucher, diskon, poin_didapat, user_id) VALUES (?, ?, ?, ?, 0, 0, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sisssssssdii", 
    $uuid,                  
    $meja_id,               
    $nama,                  
    $total_akhir_server,    
    $metode,                
    $status_pesanan,        
    $snap_token,            
    $midtrans_order_id,     
    $kode_voucher,          
    $diskon_real,           
    $poin_dapat,            
    $user_id_db             
);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stok = $koneksi->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
    
    foreach ($fixed_items as $item) {
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $item['qty'], $item['harga_satuan'], $item['subtotal']);
        $stmt_detail->execute();
        
        $stmt_stok->bind_param("ii", $item['qty'], $item['id']);
        $stmt_stok->execute();
    }
    
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");
    
    if(!empty($kode_voucher)) {
        $kode_safe = $koneksi->real_escape_string($kode_voucher);
        $koneksi->query("UPDATE vouchers SET stok = stok - 1 WHERE kode = '$kode_safe'");
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'uuid' => $uuid, 'snap_token' => $snap_token]);
} else {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>