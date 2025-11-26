<?php
session_start();
require_once '../../auth/koneksi.php';

// Header wajib untuk SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Matikan buffer
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// 1. Ambil Data Session
$level = $_SESSION['level'] ?? '';
$user_cabang = $_SESSION['cabang_id'] ?? 0;

// [PENTING] TUTUP SESI AGAR TIDAK MENGUNCI HALAMAN LAIN
// Kita sudah ambil datanya ($level & $user_cabang), jadi sesi bisa ditutup.
session_write_close(); 

$filter_cabang = isset($_GET['cabang_id']) ? $_GET['cabang_id'] : '';
$last_hash = null;

// LOOPING TANPA HENTI
while (true) {
    
    // Query Data Meja (Pastikan koneksi database tetap fresh)
    // Jika koneksi putus di tengah jalan, buat koneksi baru (opsional, biasanya mysqli handle ini)
    
    $sql_meja = "SELECT meja.id, meja.nomor_meja, meja.status, meja.qr_token, cabang.nama_cabang 
                 FROM meja 
                 JOIN cabang ON meja.cabang_id = cabang.id";

    if ($level == 'admin' && !empty($filter_cabang)) {
        $sql_meja .= " WHERE meja.cabang_id = '$filter_cabang'";
    } elseif ($level != 'admin') {
        $sql_meja .= " WHERE meja.cabang_id = '$user_cabang'";
    }

    $sql_meja .= " ORDER BY cabang.nama_cabang ASC, CAST(meja.nomor_meja AS UNSIGNED) ASC";
    
    $result = $koneksi->query($sql_meja);
    
    // Error Handling jika DB putus
    if (!$result) {
        break; // Keluar loop biar SSE reconnect otomatis
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['qr_url'] = "https://" . $_SERVER['HTTP_HOST'] . "/penjualanv2/penjualan/?token=" . $row['qr_token'];
        $data[] = $row;
    }

    // Cek Perubahan
    $current_hash = md5(json_encode($data));

    if ($current_hash !== $last_hash) {
        echo "data: " . json_encode(['status' => 'success', 'data' => $data]) . "\n\n";
        $last_hash = $current_hash;
    }

    flush();
    
    // Cek koneksi klien
    if (connection_aborted()) break;

    sleep(1); // Istirahat 1 detik
}
?>