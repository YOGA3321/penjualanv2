<?php
session_start();
require_once '../../auth/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';

// --- 1. KONFIRMASI BAYAR (TUNAI) ---
if ($action == 'konfirmasi_bayar') {
    $uang = $_POST['uang_bayar'];
    $kembali = $_POST['kembalian'];
    
    $query = "UPDATE transaksi SET 
              status_pembayaran = 'settlement', 
              status_pesanan = 'diproses', 
              uang_bayar = '$uang', 
              kembalian = '$kembali' 
              WHERE id = '$id'";
              
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Lunas! Pesanan dikirim ke Dapur.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
} 

// --- 2. SELESAI MASAK (DAPUR) ---
elseif ($action == 'selesai_masak') {
    $query = "UPDATE transaksi SET status_pesanan = 'siap_saji' WHERE id = '$id'";
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Pesanan Siap Disajikan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
}

// --- 3. KOSONGKAN MEJA (MANUAL) ---
elseif ($action == 'kosongkan_meja') {
    // Selesaikan transaksi yang nyangkut
    $koneksi->query("UPDATE transaksi SET status_pesanan = 'selesai' WHERE meja_id = '$id' AND status_pesanan != 'selesai'");
    // Set Meja Kosong
    $query = "UPDATE meja SET status = 'kosong' WHERE id = '$id'";
    
    if ($koneksi->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Meja Kosong & Siap Pakai.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $koneksi->error]);
    }
}

// --- 4. [BARU] TOLAK PESANAN ---
elseif ($action == 'tolak_pesanan') {
    // Ambil data transaksi dulu untuk restore stok
    $trx = $koneksi->query("SELECT id, meja_id FROM transaksi WHERE id = '$id'")->fetch_assoc();
    
    if($trx) {
        // A. Update Status Transaksi -> Cancel
        $koneksi->query("UPDATE transaksi SET status_pembayaran = 'cancel', status_pesanan = 'cancel' WHERE id = '$id'");
        
        // B. Kembalikan Stok (Restore)
        $details = $koneksi->query("SELECT menu_id, qty FROM transaksi_detail WHERE transaksi_id = '$id'");
        while($d = $details->fetch_assoc()) {
            $koneksi->query("UPDATE menu SET stok = stok + ".$d['qty']." WHERE id = '".$d['menu_id']."'");
        }

        // C. Kosongkan Meja (Jika tidak ada pesanan aktif lain di meja itu)
        $meja_id = $trx['meja_id'];
        $cek_aktif = $koneksi->query("SELECT id FROM transaksi WHERE meja_id = '$meja_id' AND status_pesanan NOT IN ('selesai', 'cancel') AND id != '$id'");
        
        if($cek_aktif->num_rows == 0) {
            $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '$meja_id'");
        }

        echo json_encode(['status' => 'success', 'message' => 'Pesanan ditolak & Stok dikembalikan.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan']);
    }
}
?>