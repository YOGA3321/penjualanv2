<?php
require_once '../../auth/koneksi.php';

// Pastikan library Midtrans sudah ada nanti
// require_once dirname(__FILE__) . '/../../vendor/autoload.php';

// use Midtrans\Config;
// use Midtrans\Notification;

// Config::$serverKey = 'SB-Mid-server-xxxx'; // Sesuaikan key
// Config::$isProduction = false;

try {
    // 1. Terima Data JSON (Dari Router Central)
    $json = file_get_contents('php://input');
    $notif = json_decode($json);

    if (!$notif) exit("No data");

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;
    
    // Ambil UUID Asli dari Custom Field
    $uuid_db = $notif->custom_field1 ?? null;

    // Jika custom_field kosong, coba parsing manual dari order_id (RESTO-UUID-TIMESTAMP)
    if (!$uuid_db) {
        $parts = explode('-', $order_id);
        // Ini berisiko kalau UUID dipotong, tapi sebagai fallback
        // Lebih baik mengandalkan custom_field1
        if (isset($parts[1])) { 
            // Cari di DB transaksi yang uuid-nya *diawali* potongan ini
            $short = $koneksi->real_escape_string($parts[1]);
            $q = $koneksi->query("SELECT uuid FROM transaksi WHERE uuid LIKE '$short%' LIMIT 1");
            if ($row = $q->fetch_assoc()) {
                $uuid_db = $row['uuid'];
            }
        }
    }

    if (!$uuid_db) {
        http_response_code(404);
        exit("Transaction not found in DB");
    }

    // 2. Tentukan Status
    $status_db = null;
    if ($transaction == 'capture') {
        if ($fraud == 'challenge') {
            $status_db = 'pending';
        } else {
            $status_db = 'settlement';
        }
    } else if ($transaction == 'settlement') {
        $status_db = 'settlement';
    } else if ($transaction == 'pending') {
        $status_db = 'pending';
    } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
        $status_db = 'cancel';
    }

    // 3. Update Database
    if ($status_db == 'settlement') {
        // Jika LUNAS: Update Status Pembayaran & Status Pesanan (Masuk Dapur)
        $koneksi->query("UPDATE transaksi SET 
                         status_pembayaran = 'settlement', 
                         status_pesanan = 'diproses' 
                         WHERE uuid = '$uuid_db'");
    } else if ($status_db == 'cancel') {
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE uuid = '$uuid_db'");
    }

    http_response_code(200);
    echo "OK";

} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
?>