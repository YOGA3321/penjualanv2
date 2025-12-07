<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}
require_once '../../auth/koneksi.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php'; 

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';

// --- 1. SINKRONISASI MANUAL MIDTRANS ---
if ($action == 'sync_midtrans') {
    $q = $koneksi->query("SELECT * FROM transaksi WHERE id = '$id'");
    $trx = $q->fetch_assoc();

    if(!$trx || empty($trx['midtrans_id'])) {
        echo json_encode(['status'=>'error', 'message'=>'ID Midtrans tidak ditemukan']); exit;
    }

    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; // SESUAIKAN KEY!
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;

    try {
        $status = \Midtrans\Transaction::status($trx['midtrans_id']);
        $transaction = $status->transaction_status;
        $fraud = $status->fraud_status;

        $new_status = 'pending';
        if ($transaction == 'capture') {
            $new_status = ($fraud == 'challenge') ? 'challenge' : 'settlement';
        } else if ($transaction == 'settlement') {
            $new_status = 'settlement';
        } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
            $new_status = 'cancel';
        }

        // [FIX UTAMA] UPDATE LOGIKA
        // Selalu update jika ada perubahan status pembayaran
        if ($new_status != $trx['status_pembayaran']) {
            $uuid = $trx['uuid'];
            
            // 1. Update Status Pembayaran
            $koneksi->query("UPDATE transaksi SET status_pembayaran = '$new_status' WHERE uuid = '$uuid'");
            
            // 2. Jika LUNAS -> Ubah Pesanan jadi DIPROSES & Tambah Poin
            if ($new_status == 'settlement') {
                $koneksi->query("UPDATE transaksi SET status_pesanan = 'diproses' WHERE uuid = '$uuid'");
                
                if ($trx['user_id'] && $trx['poin_didapat'] > 0) {
                    $koneksi->query("UPDATE users SET poin = poin + ".$trx['poin_didapat']." WHERE id = '".$trx['user_id']."'");
                }
                echo json_encode(['status'=>'success', 'message'=>'Pembayaran LUNAS! Pesanan diproses.']);
            } 
            // 3. Jika Gagal -> Cancel & Restore
            else if ($new_status == 'cancel' || $new_status == 'expire') {
                $koneksi->query("UPDATE transaksi SET status_pesanan = 'cancel' WHERE uuid = '$uuid'");
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '".$trx['meja_id']."'");
                // Restore Stok
                $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '".$trx['id']."'");
                while($d = $details->fetch_assoc()) {
                    $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
                }
                echo json_encode(['status'=>'warning', 'message'=>'Transaksi Dibatalkan/Expired.']);
            } else {
                echo json_encode(['status'=>'info', 'message'=>'Status: '.strtoupper($new_status)]);
            }
        } else {
            // [FIX CRITICAL] Jika Midtrans = Settlement TAPI DB masih Menunggu Bayar (Mungkin webhook gagal update status_pesanan)
            if ($new_status == 'settlement' && $trx['status_pesanan'] == 'menunggu_bayar') {
                $koneksi->query("UPDATE transaksi SET status_pesanan = 'diproses' WHERE id = '$id'");
                echo json_encode(['status'=>'success', 'message'=>'Sync: Pesanan kini DIPROSES.']);
            } else {
                echo json_encode(['status'=>'info', 'message'=>'Status sudah sesuai ('.strtoupper($new_status).')']);
            }
        }

    } catch (Exception $e) {
        echo json_encode(['status'=>'error', 'message'=>'Midtrans Error: '.$e->getMessage()]);
    }
}

// --- 2. KONFIRMASI TUNAI ---
elseif ($action == 'konfirmasi_tunai') {
    $q = $koneksi->query("SELECT * FROM transaksi WHERE id = '$id'");
    $trx = $q->fetch_assoc();
    
    if($trx) {
        $uang = $_POST['uang_bayar'] ?? 0;
        $kembali = $_POST['kembalian'] ?? 0;

        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'settlement', status_pesanan = 'diproses', metode_pembayaran = 'tunai', uang_bayar = '$uang', kembalian = '$kembali' WHERE id = '$id'");
        
        if ($trx['user_id'] && $trx['poin_didapat'] > 0) {
            $koneksi->query("UPDATE users SET poin = poin + ".$trx['poin_didapat']." WHERE id = '".$trx['user_id']."'");
        }
        echo json_encode(['status'=>'success', 'uuid' => $trx['uuid']]);
    } else {
        echo json_encode(['status'=>'error', 'msg'=>'Data invalid']);
    }
}

// --- 3. TOLAK PESANAN ---
elseif ($action == 'tolak_pesanan') {
    $trx = $koneksi->query("SELECT id, meja_id FROM transaksi WHERE id = '$id'")->fetch_assoc();
    if($trx) {
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE id = '$id'");
        $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '$id'");
        while($d = $details->fetch_assoc()) {
            $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
        }
        $meja_id = $trx['meja_id'];
        $cek_aktif = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '$id'");
        if($cek_aktif->num_rows == 0) $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
        
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Data invalid']);
    }
}

// --- 4. UPDATE STATUS (Dapur) ---
elseif ($action == 'update_status') {
    $status = $_POST['status']; 
    $koneksi->query("UPDATE transaksi SET status_pesanan = '$status' WHERE id = '$id'");
    if($status == 'selesai') {
        $trx = $koneksi->query("SELECT meja_id FROM transaksi WHERE id='$id'")->fetch_assoc();
        if($trx) {
            $meja_id = $trx['meja_id'];
            $cek_lain = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '$id'");
            if($cek_lain->num_rows == 0) {
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
                $koneksi->query("UPDATE reservasi SET status = 'selesai' WHERE meja_id = '$meja_id' AND status = 'checkin'");
            }
        }
    }
    echo json_encode(['status'=>'success']);
}
?>