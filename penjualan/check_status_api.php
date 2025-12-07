<?php
require_once '../auth/koneksi.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

header('Content-Type: application/json');

$uuid = $_GET['uuid'] ?? '';
if(empty($uuid)) { echo json_encode(['status'=>'error']); exit; }

// Cek Status Real ke Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; // Sesuaikan
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;

$q = $koneksi->query("SELECT * FROM transaksi WHERE uuid = '$uuid'");
$trx = $q->fetch_assoc();

if ($trx) {
    if ($trx['metode_pembayaran'] == 'midtrans' && $trx['status_pembayaran'] == 'pending') {
        try {
            $status = \Midtrans\Transaction::status($trx['midtrans_id']);
            $transaction = $status->transaction_status;
            
            $new_status = 'pending';
            if ($transaction == 'settlement' || $transaction == 'capture') {
                $new_status = 'settlement';
            } else if ($transaction == 'expire') {
                $new_status = 'expire';
            } else if ($transaction == 'cancel') {
                $new_status = 'cancel';
            }

            // Jika status berubah jadi LUNAS
            if ($new_status == 'settlement' && $trx['status_pembayaran'] != 'settlement') {
                $koneksi->query("UPDATE transaksi SET status_pembayaran = 'settlement', status_pesanan = 'diproses' WHERE uuid = '$uuid'");
                
                // [FIX POIN BACKUP]
                $uid = $trx['user_id'];
                $poin = $trx['poin_didapat'];
                if (!empty($uid) && $poin > 0) {
                    $koneksi->query("UPDATE users SET poin = poin + $poin WHERE id = '$uid'");
                }
                
                $trx['status_pembayaran'] = 'settlement'; // Update array utk response
            } else {
                // Update status lain (expire/cancel)
                $koneksi->query("UPDATE transaksi SET status_pembayaran = '$new_status' WHERE uuid = '$uuid'");
            }
        } catch (Exception $e) {
            // Ignore error midtrans connection
        }
    }
    
    echo json_encode(['status'=>'success', 'data'=>$trx]);
} else {
    echo json_encode(['status'=>'error']);
}
?>