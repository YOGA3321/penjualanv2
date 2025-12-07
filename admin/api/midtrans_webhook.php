<?php
require_once '../../auth/koneksi.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;

try {
    $notif = new \Midtrans\Notification();
} catch (Exception $e) {
    exit($e->getMessage());
}

$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$order_id = $notif->order_id;
$fraud = $notif->fraud_status;

// Cari Transaksi Berdasarkan Order ID Midtrans
$q = $koneksi->query("SELECT * FROM transaksi WHERE midtrans_id = '$order_id'");
$trx = $q->fetch_assoc();

if (!$trx) exit("Transaksi tidak ditemukan");

// Logika Status
$status_db = 'pending';
if ($transaction == 'capture') {
    if ($type == 'credit_card') {
        $status_db = ($fraud == 'challenge') ? 'challenge' : 'settlement';
    }
} else if ($transaction == 'settlement') {
    $status_db = 'settlement';
} else if ($transaction == 'pending') {
    $status_db = 'pending';
} else if ($transaction == 'deny') {
    $status_db = 'failure';
} else if ($transaction == 'expire') {
    $status_db = 'expire';
} else if ($transaction == 'cancel') {
    $status_db = 'cancel';
}

// Update Status Transaksi
$uuid = $trx['uuid'];
$koneksi->query("UPDATE transaksi SET status_pembayaran = '$status_db' WHERE uuid = '$uuid'");

// [FIX POIN] JIKA LUNAS (SETTLEMENT) DAN BELUM DITAMBAHKAN
// Cek apakah status sebelumnya bukan settlement, biar gak double poin
if ($status_db == 'settlement' && $trx['status_pembayaran'] != 'settlement') {
    
    // Update Status Pesanan jadi Diproses
    $koneksi->query("UPDATE transaksi SET status_pesanan = 'diproses' WHERE uuid = '$uuid'");

    // Tambah Poin ke User
    $uid = $trx['user_id'];
    $poin = $trx['poin_didapat'];
    
    if (!empty($uid) && $poin > 0) {
        $koneksi->query("UPDATE users SET poin = poin + $poin WHERE id = '$uid'");
    }
}
?>