<?php
session_start();
require_once '../../auth/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error']); exit; }

$uuid = $_GET['uuid'] ?? '';

// Ambil Header Transaksi
$query = "SELECT t.*, m.nomor_meja, u.nama as nama_kasir 
          FROM transaksi t 
          LEFT JOIN meja m ON t.meja_id = m.id 
          LEFT JOIN users u ON t.user_id = u.id
          WHERE t.uuid = '$uuid'";
$trx = $koneksi->query($query)->fetch_assoc();

if (!$trx) { echo json_encode(['status'=>'error', 'message'=>'Data tidak ditemukan']); exit; }

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