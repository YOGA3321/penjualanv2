<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}
require_once '../../auth/koneksi.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php'; // Load Library Midtrans

header('Content-Type: application/json');

$action = $_POST['action'] ?? ''; 
if(empty($action)) $action = $_GET['action'] ?? '';

$id = $_POST['id'] ?? '';

// --- 1. SINKRONISASI MANUAL MIDTRANS (BARU) ---
if ($action == 'sync_midtrans') {
    $q = $koneksi->query("SELECT * FROM transaksi WHERE id = '$id'");
    $trx = $q->fetch_assoc();

    if(!$trx || empty($trx['midtrans_id'])) {
        echo json_encode(['status'=>'error', 'message'=>'ID Midtrans tidak ditemukan']); exit;
    }

    // Config Midtrans
    \Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY'];
    \Midtrans\Config::$isProduction = ($_ENV['MIDTRANS_IS_PRODUCTION'] === 'true');
    \Midtrans\Config::$isSanitized = true;

    try {
        // Tembak API Midtrans Langsung
        $status = \Midtrans\Transaction::status($trx['midtrans_id']);
        $transaction = $status->transaction_status;
        $fraud = $status->fraud_status;

        $new_status = 'pending';
        // Terjemahkan Status Midtrans ke Database Kita
        if ($transaction == 'capture') {
            $new_status = ($fraud == 'challenge') ? 'challenge' : 'settlement';
        } else if ($transaction == 'settlement') {
            $new_status = 'settlement';
        } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
            $new_status = 'cancel';
        }

        // Update Database Jika Status Berubah
        if ($new_status != $trx['status_pembayaran']) {
            $uuid = $trx['uuid'];
            
            // Update Status Pembayaran
            $koneksi->query("UPDATE transaksi SET status_pembayaran = '$new_status' WHERE uuid = '$uuid'");
            
            // Jika Lunas -> Ubah Pesanan Jadi 'Diproses' & Tambah Poin
            if ($new_status == 'settlement') {
                $koneksi->query("UPDATE transaksi SET status_pesanan = 'diproses' WHERE uuid = '$uuid'");
                
                // Tambah Poin (Cek agar tidak double)
                if ($trx['user_id'] && $trx['poin_didapat'] > 0) {
                    $koneksi->query("UPDATE users SET poin = poin + ".$trx['poin_didapat']." WHERE id = '".$trx['user_id']."'");
                }
                echo json_encode(['status'=>'success', 'message'=>'Pembayaran LUNAS! Data diperbarui.']);
            } 
            // Jika Gagal -> Cancel Pesanan & Restore Stok
            else if ($new_status == 'cancel') {
                $koneksi->query("UPDATE transaksi SET status_pesanan = 'cancel' WHERE uuid = '$uuid'");
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '".$trx['meja_id']."'");
                
                // Restore Stok
                $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '".$trx['id']."'");
                while($d = $details->fetch_assoc()) {
                    $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
                }
                echo json_encode(['status'=>'warning', 'message'=>'Transaksi Dibatalkan/Expired.']);
            } else {
                echo json_encode(['status'=>'info', 'message'=>'Status saat ini: '.strtoupper($new_status)]);
            }
        } else {
            // Status Masih Sama (Misal masih Pending)
            if($new_status == 'settlement') {
                 // Kasus aneh: Status lunas tapi pesanan belum diproses
                 $koneksi->query("UPDATE transaksi SET status_pesanan = 'diproses' WHERE uuid = '$trx[uuid]'");
                 echo json_encode(['status'=>'success', 'message'=>'Data disinkronkan.']);
            } else {
                echo json_encode(['status'=>'info', 'message'=>'Status belum berubah ('.strtoupper($new_status).')']);
            }
        }

    } catch (Exception $e) {
        echo json_encode(['status'=>'error', 'message'=>'Midtrans Error: '.$e->getMessage()]);
    }
}

// --- 2. KONFIRMASI BAYAR (TUNAI) ---
elseif ($action == 'konfirmasi_tunai') {
    // ... (Kode sama seperti sebelumnya) ...
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
    // ... (Kode sama seperti sebelumnya) ...
    $trx = $koneksi->query("SELECT id, meja_id FROM transaksi WHERE id = '$id'")->fetch_assoc();
    if($trx) {
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE id = '$id'");
        $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '$id'");
        while($d = $details->fetch_assoc()) {
            $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
        }
        $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '".$trx['meja_id']."'");
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Data invalid']);
    }
}

// --- 4. UPDATE STATUS (Dapur/Selesai) ---
elseif ($action == 'update_status') {
    // ... (Kode sama seperti sebelumnya) ...
    $status = $_POST['status']; 
    $koneksi->query("UPDATE transaksi SET status_pesanan = '$status' WHERE id = '$id'");
    if($status == 'selesai') {
        $trx = $koneksi->query("SELECT meja_id FROM transaksi WHERE id='$id'")->fetch_assoc();
        if($trx) {
            $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '".$trx['meja_id']."'");
            $koneksi->query("UPDATE reservasi SET status = 'selesai' WHERE meja_id = '".$trx['meja_id']."' AND status = 'checkin'");
        }
    }
    echo json_encode(['status'=>'success']);
}
?>