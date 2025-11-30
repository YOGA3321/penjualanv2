<?php
require_once '../auth/koneksi.php';
header('Content-Type: application/json');

$uuid = $_GET['uuid'] ?? '';
$q = $koneksi->query("SELECT status_pembayaran FROM transaksi WHERE uuid = '$uuid'");
$row = $q->fetch_assoc();

if($row) {
    echo json_encode(['status' => 'success', 'data' => $row]);
} else {
    echo json_encode(['status' => 'error']);
}
?>