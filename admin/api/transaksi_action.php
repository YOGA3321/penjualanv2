<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}
require_once '../../auth/koneksi.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? ''; // Pakai POST untuk action juga biar konsisten
if(empty($action)) $action = $_GET['action'] ?? ''; // Fallback GET

$id = $_POST['id'] ?? '';

// --- 1. KONFIRMASI BAYAR (TUNAI) ---
if ($action == 'konfirmasi_tunai') {
    // Ambil Data Transaksi Dulu
    $q = $koneksi->query("SELECT * FROM transaksi WHERE id = '$id'");
    $trx = $q->fetch_assoc();
    
    if($trx) {
        if($trx['status_pembayaran'] != 'settlement') {
            // Update Jadi Lunas & Diproses
            // Simpan nominal bayar jika dikirim (opsional)
            $uang = $_POST['uang_bayar'] ?? 0; // Jika dikirim dari frontend
            $kembali = $_POST['kembalian'] ?? 0;

            // Jika frontend tidak kirim uang (hanya konfirmasi), biarkan 0 atau update query sesuai kebutuhan
            // Disini kita update status saja yang utama
            $koneksi->query("UPDATE transaksi SET status_pembayaran = 'settlement', status_pesanan = 'diproses' WHERE id = '$id'");
            
            // Tambah Poin ke User (Jika Member)
            $uid = $trx['user_id'];
            $poin = $trx['poin_didapat'];
            if (!empty($uid) && $poin > 0) {
                $koneksi->query("UPDATE users SET poin = poin + $poin WHERE id = '$uid'");
            }
            
            // Return UUID agar bisa cetak struk
            echo json_encode([
                'status'=>'success', 
                'msg'=>'Pembayaran diterima', 
                'uuid' => $trx['uuid'] // PENTING UNTUK CETAK
            ]);
        } else {
            echo json_encode(['status'=>'error', 'msg'=>'Transaksi sudah lunas sebelumnya']);
        }
    } else {
        echo json_encode(['status'=>'error', 'msg'=>'Data tidak ditemukan']);
    }
}

// --- 2. TOLAK PESANAN ---
elseif ($action == 'tolak_pesanan') {
    $trx = $koneksi->query("SELECT id, meja_id FROM transaksi WHERE id = '$id'")->fetch_assoc();
    
    if($trx) {
        // A. Cancel Transaksi
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE id = '$id'");
        
        // B. Restore Stok
        $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '$id'");
        while($d = $details->fetch_assoc()) {
            $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
        }

        // C. Kosongkan Meja (Jika tidak ada pesanan aktif lain)
        $meja_id = $trx['meja_id'];
        $cek_aktif = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '$id'");
        if($cek_aktif->num_rows == 0) {
            $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
        }
        
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Data invalid']);
    }
}

// --- 3. UPDATE STATUS (Dapur/Saji/Selesai) ---
elseif ($action == 'update_status') {
    $status = $_POST['status']; 
    
    $koneksi->query("UPDATE transaksi SET status_pesanan = '$status' WHERE id = '$id'");
    
    // Jika Selesai -> Kosongkan Meja
    if($status == 'selesai') {
        $trx = $koneksi->query("SELECT meja_id FROM transaksi WHERE id='$id'")->fetch_assoc();
        if($trx) {
            $meja_id = $trx['meja_id'];
            // Cek apakah masih ada pesanan lain di meja ini?
            $cek_lain = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '$id'");
            if($cek_lain->num_rows == 0) {
                $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
                // Selesaikan reservasi juga
                $koneksi->query("UPDATE reservasi SET status = 'selesai' WHERE meja_id = '$meja_id' AND status = 'checkin'");
            }
        }
    }
    
    echo json_encode(['status'=>'success']);
}
?>