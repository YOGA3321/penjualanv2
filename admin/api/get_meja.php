<?php
session_start();
require_once '../../auth/koneksi.php'; // Sesuaikan path ke koneksi

header('Content-Type: application/json');

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$level = $_SESSION['level'] ?? '';
$user_cabang = $_SESSION['cabang_id'] ?? 0;

// 2. Ambil Filter dari Parameter URL (dikirim oleh JS nanti)
$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';

// 3. Logika Query (Sama seperti di admin/meja.php)
$sql_meja = "SELECT meja.*, cabang.nama_cabang 
             FROM meja 
             JOIN cabang ON meja.cabang_id = cabang.id";

if ($level == 'admin') {
    // Jika Admin mengirim filter cabang
    if (!empty($filter_cabang)) {
        $sql_meja .= " WHERE meja.cabang_id = '$filter_cabang'";
    }
} else {
    // Karyawan hanya lihat cabangnya sendiri
    $sql_meja .= " WHERE meja.cabang_id = '$user_cabang'";
}

$sql_meja .= " ORDER BY cabang.nama_cabang ASC, CAST(meja.nomor_meja AS UNSIGNED) ASC";

$result = $koneksi->query($sql_meja);
$data = [];

while ($row = $result->fetch_assoc()) {
    // Siapkan data yang dibutuhkan JS
    $row['qr_url'] = "https://" . $_SERVER['HTTP_HOST'] . "/penjualanv2/pelanggan/order?token=" . $row['qr_token'];
    $data[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $data]);
?>