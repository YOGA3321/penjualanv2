<?php
// admin/api/midtrans_webhook.php
require_once '../../auth/koneksi.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY'];
\Midtrans\Config::$isProduction = ($_ENV['MIDTRANS_IS_PRODUCTION'] === 'true');
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

// 1. Cari Transaksi Berdasarkan Order ID Midtrans
// Kita perlu mengambil data total_harga juga untuk update uang_bayar nanti
$q = $koneksi->query("SELECT * FROM transaksi WHERE midtrans_id = '$order_id'");
$trx = $q->fetch_assoc();

if (!$trx) exit("Transaksi tidak ditemukan");

$uuid = $trx['uuid'];
$status_sebelumnya = $trx['status_pembayaran'];

// 2. Tentukan Status Baru
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
    $status_db = 'gagal'; // Sesuaikan enum db (failure/gagal)
} else if ($transaction == 'expire') {
    $status_db = 'kadaluarsa'; // Sesuaikan enum db (expire/kadaluarsa)
} else if ($transaction == 'cancel') {
    $status_db = 'dibatalkan'; // Sesuaikan enum db (cancel/dibatalkan)
}

// 3. LOGIKA UPDATE DATABASE
// Update Status Pembayaran
$koneksi->query("UPDATE transaksi SET status_pembayaran = '$status_db' WHERE uuid = '$uuid'");


// A. JIKA SUKSES (SETTLEMENT)
if ($status_db == 'settlement' && $status_sebelumnya != 'settlement') {
    
    // [FIX 1] Update uang_bayar agar laporan keuangan valid
    // Kita isi uang_bayar sama dengan total_harga karena sudah lunas via sistem
    $total_tagihan = $trx['total_harga'];
    $koneksi->query("UPDATE transaksi SET 
        status_pesanan = 'diproses', 
        uang_bayar = '$total_tagihan' 
        WHERE uuid = '$uuid'");

    // Tambah Poin ke User
    $uid = $trx['user_id'];
    $poin = $trx['poin_didapat'];
    if (!empty($uid) && $poin > 0) {
        $koneksi->query("UPDATE users SET poin = poin + $poin WHERE id = '$uid'");
    }

    // Tulis Trigger untuk SSE (Agar layar kasir bunyi)
    $data_sse = [
        'payment_event' => [
            'order_id' => $order_id,
            'status' => 'settlement',
            'pesan' => 'Pembayaran Diterima'
        ]
    ];
    @file_put_contents('../../sse_trigger.txt', json_encode($data_sse));
}

// B. JIKA GAGAL / EXPIRED / CANCEL
// [FIX 2] Kembalikan Stok (Restock) jika transaksi batal
else if (in_array($status_db, ['gagal', 'kadaluarsa', 'dibatalkan']) && $status_sebelumnya == 'pending') {
    
    // Ambil detail item yang dipesan
    $q_detail = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '{$trx['id']}'");
    
    while($row = $q_detail->fetch_assoc()) {
        $menu_id = $row['menu_id'];
        $qty = $row['qty'];
        
        // Kembalikan stok ke menu
        $koneksi->query("UPDATE menu SET stok = stok + $qty WHERE id = '$menu_id'");
    }
    
    // Opsional: Update status pesanan jadi batal juga
    $koneksi->query("UPDATE transaksi SET status_pesanan = 'dibatalkan' WHERE uuid = '$uuid'");
}

echo "OK - Status Updated to $status_db";
?>