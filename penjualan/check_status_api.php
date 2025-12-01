<?php
// check_status_api.php
ini_set('display_errors', 0);
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

// 3. Jika Masih PENDING & MIDTRANS -> Cek ke Midtrans Server
if ($trx['status_pembayaran'] == 'pending' && $trx['metode_pembayaran'] == 'midtrans' && !empty($trx['midtrans_id'])) {
    
    // Konfigurasi Server Key
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;

    try {
        $status_midtrans = \Midtrans\Transaction::status($trx['midtrans_id']);
        $transaction_status = $status_midtrans->transaction_status;
        $fraud_status = $status_midtrans->fraud_status;

        $new_status = 'pending';
        if ($transaction_status == 'capture') {
            $new_status = ($fraud_status == 'challenge') ? 'pending' : 'settlement';
        } else if ($transaction_status == 'settlement') {
            $new_status = 'settlement';
        } else if ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
            $new_status = 'cancel';
        }

        // --- UPDATE DATABASE ---

        if ($new_status == 'settlement') {
            // LUNAS: Update jadi diproses
            $koneksi->query("UPDATE transaksi SET status_pembayaran = 'settlement', status_pesanan = 'diproses' WHERE uuid = '$uuid'");
            $trx['status_pembayaran'] = 'settlement';
        } 
        
        else if ($new_status == 'cancel') {
            // [LOGIKA PEMBATALAN OTOMATIS]
            
            // A. Update Status Transaksi jadi Cancel
            $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE uuid = '$uuid'");
            
            // B. Kembalikan Stok (Loop item di transaksi ini)
            $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '".$trx['id']."'");
            while($d = $details->fetch_assoc()) {
                $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
            }

            // C. Kosongkan Meja (Hanya jika tidak ada pesanan aktif lain di meja tersebut)
            $meja_id = $trx['meja_id'];
            // Cek apakah ada transaksi lain di meja ini yang statusnya BUKAN (selesai/cancel) dan BUKAN transaksi ini
            $cek_aktif = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '".$trx['id']."'");
            
            if($cek_aktif->num_rows == 0) {
                // Tidak ada pesanan lain, meja jadi kosong
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
            }

            $trx['status_pembayaran'] = 'cancel';
        }

    } catch (Exception $e) {
        // Error koneksi midtrans, biarkan data lama
    }
}

echo json_encode(['status' => 'success', 'data' => $trx]);
?>