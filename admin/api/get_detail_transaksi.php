<?php
session_start();
require_once '../../auth/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; 
}

// [FIX] Ubah penerimaan parameter dari 'uuid' menjadi 'id'
$id = $_GET['id'] ?? '';

if(empty($id)) {
    echo json_encode(['status'=>'error', 'message'=>'ID Transaksi kosong']); exit;
}

// Ambil Header Transaksi
// [FIX] Query WHERE t.id = '$id' (Bukan UUID)
$query = "SELECT t.*, m.nomor_meja, u.nama as nama_kasir, c.nama_cabang
          FROM transaksi t 
          LEFT JOIN meja m ON t.meja_id = m.id 
          LEFT JOIN users u ON t.user_id = u.id
          LEFT JOIN cabang c ON m.cabang_id = c.id
          WHERE t.id = '$id'";

$trx = $koneksi->query($query)->fetch_assoc();

if (!$trx) { 
    echo json_encode(['status'=>'error', 'message'=>'Data transaksi tidak ditemukan']); exit; 
}

// Ambil Detail Item
$query_detail = "SELECT d.*, menu.nama_menu 
                 FROM transaksi_detail d 
                 JOIN menu ON d.menu_id = menu.id 
                 WHERE d.transaksi_id = '".$trx['id']."'";
$res_detail = $koneksi->query($query_detail);

$items = [];
while($row = $res_detail->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    'status' => 'success',
    'header' => $trx,
    'items' => $items
]);
?>