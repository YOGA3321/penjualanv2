<?php
session_start();
require_once '../../auth/koneksi.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

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
    // [FIX KRUSIAL]
    // HANYA ambil yang status_pesanan = 'diproses' (Artinya sudah dikonfirmasi admin/lunas)
    // DAN status_pembayaran = 'settlement' (LUNAS)
    
    $sql = "SELECT t.id, t.nama_pelanggan, t.created_at, m.nomor_meja, c.nama_cabang
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            JOIN cabang c ON m.cabang_id = c.id
            WHERE t.status_pesanan = 'diproses' 
            AND t.status_pembayaran = 'settlement'"; // Syarat Wajib LUNAS

    if ($level != 'admin' || (isset($_GET['view_cabang']) && $_GET['view_cabang'] != 'pusat')) {
        $sql .= " AND m.cabang_id = '$cabang_id'";
    }
    $sql .= " ORDER BY t.created_at ASC"; 

    $result = $koneksi->query($sql);
    $orders = [];
    
    while ($row = $result->fetch_assoc()) {
        $trx_id = $row['id'];
        $details = $koneksi->query("SELECT d.qty, d.catatan, mn.nama_menu 
                                    FROM transaksi_detail d 
                                    JOIN menu mn ON d.menu_id = mn.id 
                                    WHERE d.transaksi_id = '$trx_id'");
        $items = [];
        while($d = $details->fetch_assoc()) { $items[] = $d; }
        $row['items'] = $items;
        $orders[] = $row;
    }

    $current_hash = md5(json_encode($orders));

    if ($current_hash !== $last_hash) {
        echo "data: " . json_encode(['status' => 'success', 'data' => $orders]) . "\n\n";
        $last_hash = $current_hash;
    }

    flush();
    if (connection_aborted()) break;
    sleep(2);
}
?>