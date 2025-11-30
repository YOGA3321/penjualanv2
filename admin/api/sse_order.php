<?php
session_start();
require_once '../../auth/koneksi.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Matikan buffer
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
session_write_close();

$last_hash = null;

while (true) {
    // Cari Transaksi yang statusnya "menunggu_konfirmasi" (Cash) ATAU "menunggu_bayar" (Online tapi belum lunas)
    // TAPI: Untuk dashboard Admin/Kasir, fokus utama adalah yang "menunggu_konfirmasi" (Butuh aksi kasir)
    // Midtrans biasanya otomatis update jadi 'diproses' via webhook, tapi kita tampilkan juga biar admin tau ada yg lagi proses bayar
    
    $sql = "SELECT t.*, m.nomor_meja 
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            WHERE (t.status_pesanan = 'menunggu_konfirmasi' OR t.status_pesanan = 'menunggu_bayar')";

    if ($level != 'admin' || (isset($_GET['view_cabang']) && $_GET['view_cabang'] != 'pusat')) {
        $sql .= " AND m.cabang_id = '$cabang_id'";
    }
    
    $sql .= " ORDER BY t.created_at ASC";

    $result = $koneksi->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        // Ambil items sekalian untuk preview
        $trx_id = $row['id'];
        $items_res = $koneksi->query("SELECT d.qty, mn.nama_menu FROM transaksi_detail d JOIN menu mn ON d.menu_id = mn.id WHERE d.transaksi_id = '$trx_id'");
        $items = [];
        while($i = $items_res->fetch_assoc()) { $items[] = $i; }
        $row['items'] = $items;
        $data[] = $row;
    }

    $current_hash = md5(json_encode($data));

    if ($current_hash !== $last_hash) {
        echo "data: " . json_encode(['status' => 'success', 'data' => $data]) . "\n\n";
        $last_hash = $current_hash;
    }

    flush();
    if (connection_aborted()) break;
    sleep(2);
}
?>