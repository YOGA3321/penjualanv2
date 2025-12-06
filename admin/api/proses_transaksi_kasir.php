<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Load Koneksi & Vendor
require_once '../../auth/koneksi.php';
$autoload = dirname(__FILE__) . '/../../vendor/autoload.php';
if(file_exists($autoload)) require_once $autoload;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login habis']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['status'=>'error', 'message'=>'Data kosong']); exit; }

$meja_id = $input['meja_id'];
$nama = $input['nama_pelanggan'];
$total = (int)$input['total_harga'];
$items = $input['items'];
$metode = $input['metode'];
$uang_bayar = $input['uang_bayar'] ?? 0;
$kembalian = $input['kembalian'] ?? 0;

// Data Voucher & Diskon
$kode_voucher = $input['kode_voucher'] ?? NULL;
$diskon = (int)($input['diskon'] ?? 0);

// --- 1. Validasi Stok Menu ---
foreach($items as $item) {
    $q = $koneksi->query("SELECT nama_menu, stok FROM menu WHERE id = '".$item['id']."'");
    $m = $q->fetch_assoc();
    if($m['stok'] < $item['qty']) {
        echo json_encode(['status'=>'error', 'message'=>"Stok {$m['nama_menu']} kurang!"]); exit;
    }
}

// --- 2. Generate UUID & Midtrans ---
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
$snap_token = null;
$midtrans_id = null;

if($metode == 'midtrans') {
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;
    
    $midtrans_id = "POS-" . date('ymdHis') . rand(100,999);
    $params = [
        'transaction_details' => ['order_id' => $midtrans_id, 'gross_amount' => $total],
        'customer_details' => ['first_name' => $nama],
        'custom_field1' => $uuid
    ];
    try {
        $snap_token = \Midtrans\Snap::getSnapToken($params);
    } catch(Exception $e) {
        echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]); exit;
    }
}

// --- 3. Simpan Transaksi ---
$status_bayar = ($metode == 'tunai') ? 'settlement' : 'pending';
$status_pesanan = ($metode == 'tunai') ? 'diproses' : 'menunggu_bayar';

// [NOTE] Kolom kode_voucher dan diskon ditambahkan disini
$sql = "INSERT INTO transaksi (uuid, meja_id, nama_pelanggan, total_harga, uang_bayar, kembalian, status_pembayaran, metode_pembayaran, status_pesanan, snap_token, midtrans_id, kode_voucher, diskon, poin_didapat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("sisdddssssssd", $uuid, $meja_id, $nama, $total, $uang_bayar, $kembalian, $status_bayar, $metode, $status_pesanan, $snap_token, $midtrans_id, $kode_voucher, $diskon);

if($stmt->execute()) {
    $trx_id = $koneksi->insert_id;
    
    // Simpan Detail & Kurangi Stok Menu
    $stmt_det = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, menu_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_upd = $koneksi->prepare("UPDATE menu SET stok = stok - ? WHERE id = ?");
    
    foreach($items as $item) {
        $sub = $item['harga'] * $item['qty'];
        $stmt_det->bind_param("iiidd", $trx_id, $item['id'], $item['qty'], $item['harga'], $sub);
        $stmt_det->execute();
        
        $stmt_upd->bind_param("ii", $item['qty'], $item['id']);
        $stmt_upd->execute();
    }
    
    // Kurangi Stok Voucher (Jika pakai)
    if($kode_voucher) {
        $koneksi->query("UPDATE vouchers SET stok = stok - 1 WHERE kode = '$kode_voucher'");
    }
    
    // Update Meja
    $koneksi->query("UPDATE meja SET status = 'terisi' WHERE id = '$meja_id'");
    
    echo json_encode(['status'=>'success', 'snap_token'=>$snap_token]);
} else {
    echo json_encode(['status'=>'error', 'message'=>$koneksi->error]);
}
?>