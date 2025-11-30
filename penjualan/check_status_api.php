<?php
// check_status_api.php
ini_set('display_errors', 0); // Matikan error HTML
require_once '../auth/koneksi.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php'; 

header('Content-Type: application/json');

$uuid = $_GET['uuid'] ?? '';

if(empty($uuid)) {
    echo json_encode(['status' => 'error', 'message' => 'UUID kosong']); exit;
}

// 1. Ambil Data Lokal
$query = "SELECT * FROM transaksi WHERE uuid = '$uuid'";
$trx = $koneksi->query($query)->fetch_assoc();

if(!$trx) {
    echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan']); exit;
}

// 2. Jika Database sudah LUNAS, langsung kembalikan sukses
if ($trx['status_pembayaran'] == 'settlement') {
    echo json_encode(['status' => 'success', 'data' => $trx]); 
    exit;
}

// 3. Jika Masih PENDING dan Metode MIDTRANS -> Cek ke Server Midtrans (Active Inquiry)
if ($trx['status_pembayaran'] == 'pending' && $trx['metode_pembayaran'] == 'midtrans' && !empty($trx['midtrans_id'])) {
    
    // Konfigurasi
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;

    try {
        // Cek Status ke Midtrans
        $status_midtrans = \Midtrans\Transaction::status($trx['midtrans_id']);
        $transaction_status = $status_midtrans->transaction_status;
        $fraud_status = $status_midtrans->fraud_status;

        // Tentukan Status Baru
        $new_status = 'pending';
        if ($transaction_status == 'capture') {
            if ($fraud_status == 'challenge') { $new_status = 'pending'; } 
            else { $new_status = 'settlement'; }
        } else if ($transaction_status == 'settlement') {
            $new_status = 'settlement';
        } else if ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
            $new_status = 'cancel';
        }

        // JIKA STATUS BERUBAH JADI LUNAS -> UPDATE DATABASE
        if ($new_status == 'settlement') {
            $koneksi->query("UPDATE transaksi SET 
                             status_pembayaran = 'settlement', 
                             status_pesanan = 'diproses' 
                             WHERE uuid = '$uuid'");
            
            // Refresh data trx untuk respon
            $trx['status_pembayaran'] = 'settlement';
            $trx['status_pesanan'] = 'diproses';
        } 
        // Jika Expired/Cancel
        else if ($new_status == 'cancel') {
            $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE uuid = '$uuid'");
            $trx['status_pembayaran'] = 'cancel';
        }

    } catch (Exception $e) {
        // Abaikan error koneksi ke Midtrans, gunakan data lokal saja
    }
}

echo json_encode(['status' => 'success', 'data' => $trx]);
?>