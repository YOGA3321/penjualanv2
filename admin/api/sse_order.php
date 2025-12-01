<?php
session_start();
require_once '../../auth/koneksi.php';

// HEADER WAJIB HOSTING
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx/Litespeed

@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(1);

$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
// Mode lihat cabang
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
session_write_close();

// Pancingan data kosong
echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$last_hash = null;
$start = time();

while (true) {
    // Auto restart tiap 30 detik (Lebih cepat biar aman)
    if ((time() - $start) > 30) die();

    // QUERY: Pastikan mengambil semua yang belum selesai
    // Tunai -> menunggu_konfirmasi
    // Midtrans -> menunggu_bayar
    $sql = "SELECT t.*, m.nomor_meja 
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            WHERE t.status_pesanan IN ('menunggu_konfirmasi', 'menunggu_bayar')";

    // Filter cabang jika bukan admin pusat global
    if ($level != 'admin' || (isset($_GET['view_cabang']) && $_GET['view_cabang'] != 'pusat')) {
        $sql .= " AND m.cabang_id = '$cabang_id'";
    }
    
    $sql .= " ORDER BY t.created_at ASC";

    $result = $koneksi->query($sql);
    
    if (!$result) { echo "retry: 5000\n\n"; flush(); break; }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Ambil items
        $trx_id = $row['id'];
        $items_res = $koneksi->query("SELECT d.qty, mn.nama_menu FROM transaksi_detail d JOIN menu mn ON d.menu_id = mn.id WHERE d.transaksi_id = '$trx_id'");
        $items = [];
        while($i = $items_res->fetch_assoc()) $items[] = $i;
        $row['items'] = $items;
        $data[] = $row;
    }

    $json = json_encode(['status' => 'success', 'data' => $data]);
    $hash = md5($json);

    if ($hash !== $last_hash) {
        echo "data: {$json}\n\n";
        $last_hash = $hash;
        flush();
    }
    
    // Heartbeat tiap 2 detik
    echo ": keepalive\n\n";
    flush();
    
    if (connection_aborted()) break;
    sleep(2);
}
?>