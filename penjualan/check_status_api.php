<?php
// Matikan error HTML agar JSON tidak rusak
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../auth/koneksi.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php'; 

header('Content-Type: application/json');

$uuid = $_GET['uuid'] ?? '';

if(empty($uuid)) { echo json_encode(['status' => 'error', 'message' => 'UUID kosong']); exit; }

// 1. Ambil Data Lokal
$query = "SELECT * FROM transaksi WHERE uuid = '$uuid'";
$trx = $koneksi->query($query)->fetch_assoc();

if(!$trx) { echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan']); exit; }

// 2. Jika Database sudah FINAL (Settlement/Cancel), langsung kembalikan
if ($trx['status_pembayaran'] == 'settlement' || $trx['status_pembayaran'] == 'cancel') {
    echo json_encode(['status' => 'success', 'data' => $trx]); 
    exit;
}

// 3. LOGIKA SINKRONISASI MIDTRANS
if ($trx['status_pembayaran'] == 'pending' && $trx['metode_pembayaran'] == 'midtrans') {
    
    // Cek apakah midtrans_id ada?
    if (empty($trx['midtrans_id'])) {
        // Jika kosong, berarti transaksi lama (sebelum update DB). Tidak bisa dicek.
        echo json_encode(['status' => 'success', 'data' => $trx, 'info' => 'Old transaction (No Midtrans ID)']);
        exit;
    }

    // Konfigurasi Server Key
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;

    try {
        // TANYA STATUS KE MIDTRANS
        $status_midtrans = \Midtrans\Transaction::status($trx['midtrans_id']);
        $transaction_status = $status_midtrans->transaction_status;
        $fraud_status = $status_midtrans->fraud_status;

        // Tentukan Status Baru
        $new_status = 'pending';
        if ($transaction_status == 'capture') {
            $new_status = ($fraud_status == 'challenge') ? 'pending' : 'settlement';
        } else if ($transaction_status == 'settlement') {
            $new_status = 'settlement';
        } else if ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
            $new_status = 'cancel';
        }

        // UPDATE DATABASE SESUAI STATUS BARU
        updateStatusTransaksi($new_status, $uuid, $trx, $koneksi);
        
        // Refresh data untuk dikirim balik ke frontend
        $trx['status_pembayaran'] = $new_status;

    } catch (Exception $e) {
        // [PENANGANAN ERROR SPESIFIK]
        
        // Kasus 404: Transaksi tidak ditemukan di Midtrans
        // Artinya: User buka popup, tapi TIDAK pilih metode pembayaran, lalu menutup/expired.
        if ($e->getCode() == 404) {
            
            // Hitung selisih waktu dari pesanan dibuat
            $created_time = strtotime($trx['created_at']);
            $current_time = time();
            $diff_minutes = ($current_time - $created_time) / 60;

            // Jika sudah lewat 6 menit (5 menit + 1 menit toleransi)
            if ($diff_minutes > 6) {
                // ANGGAP EXPIRED/CANCEL SECARA PAKSA
                updateStatusTransaksi('cancel', $uuid, $trx, $koneksi);
                $trx['status_pembayaran'] = 'cancel';
            } else {
                // Masih dalam toleransi waktu, biarkan PENDING
                // (Mungkin user baru saja buka popup)
            }
        } else {
            // Error lain (Koneksi putus/Server Key salah)
            // Kembalikan pesan error agar bisa dibaca di Console/SweetAlert
            echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['status' => 'success', 'data' => $trx]);

// --- FUNGSI BANTUAN UPDATE DB & STOK ---
function updateStatusTransaksi($new_status, $uuid, $trx_data, $koneksi) {
    if ($new_status == 'settlement') {
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'settlement', status_pesanan = 'diproses' WHERE uuid = '$uuid'");
    } 
    else if ($new_status == 'cancel') {
        // 1. Update Status
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE uuid = '$uuid'");
        
        // 2. Kembalikan Stok (Hanya jika belum dikembalikan)
        if ($trx_data['status_pembayaran'] != 'cancel') {
            $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '".$trx_data['id']."'");
            while($d = $details->fetch_assoc()) {
                $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
            }

            // 3. Kosongkan Meja (Jika tidak ada pesanan aktif lain)
            $meja_id = $trx_data['meja_id'];
            $cek_aktif = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '".$trx_data['id']."'");
            if($cek_aktif->num_rows == 0) {
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
            }
        }
    }
}
?>