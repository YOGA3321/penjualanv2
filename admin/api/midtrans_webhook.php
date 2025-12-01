<?php
require_once '../../auth/koneksi.php';

// Pastikan library ada (jika pakai autoload composer di folder atas)
$autoload = dirname(__FILE__) . '/../../vendor/autoload.php';
if(file_exists($autoload)) require_once $autoload;

// Konfigurasi
\Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
\Midtrans\Config::$isProduction = false;

try {
    $json = file_get_contents('php://input');
    $notif = json_decode($json);

    if (!$notif) exit("No data");

    $transaction = $notif->transaction_status;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;
    
    // Cari UUID dari custom_field atau parsing order_id
    $uuid_db = $notif->custom_field1 ?? null;
    if (!$uuid_db) {
        $parts = explode('-', $order_id); // RESTO-uuid-time
        if (isset($parts[1])) { 
            $short = $koneksi->real_escape_string($parts[1]);
            $q = $koneksi->query("SELECT uuid, id, meja_id FROM transaksi WHERE uuid LIKE '$short%' LIMIT 1");
            if ($row = $q->fetch_assoc()) {
                $uuid_db = $row['uuid'];
                $trx_id_db = $row['id'];
                $meja_id_db = $row['meja_id'];
            }
        }
    } else {
        // Ambil data ID dan Meja untuk keperluan restore
        $q = $koneksi->query("SELECT id, meja_id FROM transaksi WHERE uuid = '$uuid_db'");
        $row = $q->fetch_assoc();
        $trx_id_db = $row['id'];
        $meja_id_db = $row['meja_id'];
    }

    if (!$uuid_db) { http_response_code(404); exit("Transaction not found"); }

    // Tentukan Status
    $status_db = null;
    if ($transaction == 'capture') {
        $status_db = ($fraud == 'challenge') ? 'pending' : 'settlement';
    } else if ($transaction == 'settlement') {
        $status_db = 'settlement';
    } else if ($transaction == 'pending') {
        $status_db = 'pending';
    } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
        $status_db = 'cancel';
    }

    // --- PROSES UPDATE ---

    if ($status_db == 'settlement') {
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'settlement', status_pesanan = 'diproses' WHERE uuid = '$uuid_db'");
    } 
    
    else if ($status_db == 'cancel') {
        // 1. Update Transaksi
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE uuid = '$uuid_db'");
        
        // 2. Kembalikan Stok
        if(isset($trx_id_db)) {
            $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '$trx_id_db'");
            while($d = $details->fetch_assoc()) {
                $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
            }
        }

        // 3. Reset Meja (Jika kosong)
        if(isset($meja_id_db)) {
            $cek_aktif = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id_db' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '$trx_id_db'");
            if($cek_aktif->num_rows == 0) {
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id_db'");
            }
        }
    }

    http_response_code(200);
    echo "OK";

} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
?>