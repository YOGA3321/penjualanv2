<?php
session_start();
// Header & Error Handling
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// 1. Load Koneksi & Library
require_once '../../auth/koneksi.php';
$autoload = dirname(__FILE__) . '/../../vendor/autoload.php';
if(file_exists($autoload)) require_once $autoload;

// Cek Login Staff/Admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login habis, silakan login ulang.']); exit;
}

// 2. Ambil Input JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['status'=>'error', 'message'=>'Data kosong']); exit; }

$meja_id = $input['meja_id'];
$nama = $input['nama_pelanggan'];
$metode = $input['metode'];
$uang_bayar = $input['uang_bayar'] ?? 0;
$items_client = $input['items'];
$kode_voucher = $input['kode_voucher'] ?? NULL;

// =======================================================================
// [KEAMANAN] VALIDASI HARGA SERVER-SIDE (Source of Truth)
// =======================================================================
$total_real = 0;
$fixed_items = []; 

foreach ($items_client as $item) {
    $id_menu = (int)$item['id'];
    $qty = (int)$item['qty'];
    
    // Ambil Harga Terbaru dari Database
    $q_cek = $koneksi->query("SELECT harga, harga_promo, is_promo, stok, nama_menu FROM menu WHERE id = '$id_menu'");
    $db_menu = $q_cek->fetch_assoc();
    
    if(!$db_menu) continue; 
    
    // Tentukan Harga Fix (Promo vs Normal)
    $harga_fix = $db_menu['harga'];
    if ($db_menu['is_promo'] == 1 && $db_menu['harga_promo'] > 0) {
        $harga_fix = $db_menu['harga_promo'];
    }
    
    $subtotal = $harga_fix * $qty;
    $total_real += $subtotal;
    
    $fixed_items[] = [
        'id' => $id_menu,
        'qty' => $qty,
        'harga' => $harga_fix,
        'subtotal' => $subtotal
    ];
}

// =======================================================================
// [KEAMANAN] HITUNG DISKON VOUCHER
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

// Finalisasi Angka
if ($diskon_real > $total_real) $diskon_real = $total_real;
$total_bayar_akhir = $total_real - $diskon_real;
$kembalian_real = $uang_bayar - $total_bayar_akhir;

if ($metode == 'tunai' && $uang_bayar < $total_bayar_akhir) {
    echo json_encode(['status'=>'error', 'message'=>'Uang pembayaran kurang!']); exit;
}

// =======================================================================
// PROSES INTEGRASI MIDTRANS
// =======================================================================
$uuid = uniqid('TRX-'); // ID Internal
$status_bayar = ($metode == 'tunai') ? 'settlement' : 'pending';
$status_pesanan = ($metode == 'tunai') ? 'diproses' : 'menunggu_bayar';

$snap_token = null;
$midtrans_order_id = null; // Inisialisasi variabel agar tidak error di query

if ($metode == 'midtrans') {
    \Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY']; 
    \Midtrans\Config::$isProduction = ($_ENV['MIDTRANS_IS_PRODUCTION'] === 'true');
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;
    
    // [FIX 1] Ganti Prefix jadi 'RESTO-' agar ditangkap Cloudflare Worker & Webhook
    // Format: RESTO-TAHUNBULANTANGGALJAMMENITDETIK-RANDOM
    $midtrans_order_id = "RESTO-" . date('YmdHis') . rand(100, 999);
    
    $params = [
        'transaction_details' => [
            'order_id' => $midtrans_order_id, 
            'gross_amount' => $total_bayar_akhir
        ],
        'customer_details' => [
            'first_name' => $nama
        ],
        'custom_field1' => $uuid
    ];

    try {
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]); exit;
    }
}

// =======================================================================
// SIMPAN KE DATABASE (FIX BUG: Insert midtrans_id)
// =======================================================================

// [FIX 2] Tambahkan kolom 'midtrans_id' di Query
$stmt = $koneksi->prepare("INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id, kode_voucher, diskon, poin_didapat, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)");

// Binding Parameter (Total 13 variabel + 2 hardcode di SQL = 15 kolom)
// s = string, i = int, d = double
// Urutan: uuid(s), meja(i), nama(s), total(d), uang(d), kembali(d), status_bayar(s), metode(s), status_pesan(s), snap(s), midtrans_id(s), voucher(s), diskon(d)
$stmt->bind_param("sisdddssssssd", 
    $uuid, 
    $meja_id, 
    $nama, 
    $total_bayar_akhir, 
    $uang_bayar, 
    $kembalian_real, 
    $status_bayar, 
    $metode, 
    $status_pesanan, 
    $snap_token, 
    $midtrans_order_id, // [FIX 2] ID ini harus masuk DB agar webhook bisa update status nanti
    $kode_voucher, 
    $diskon_real
);

if ($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    
    // Simpan Detail Menu
    $stmt_detail = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stok = $koneksi->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
    
    foreach ($fixed_items as $item) {
        $stmt_detail->bind_param("iiidd", $trx_id, $item['id'], $item['qty'], $item['harga'], $item['subtotal']);
        $stmt_detail->execute();
        
        $stmt_stok->bind_param("ii", $item['qty'], $item['id']);
        $stmt_stok->execute();
    }
    
    // Update Status Meja
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");
    
    // Kurangi Stok Voucher
    if($kode_voucher) {
        $koneksi->query("UPDATE vouchers SET stok = stok - 1 WHERE kode = '$kode_voucher'");
    }

    echo json_encode(['status' => 'success', 'uuid' => $uuid, 'snap_token' => $snap_token]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $koneksi->error]);
}
?>