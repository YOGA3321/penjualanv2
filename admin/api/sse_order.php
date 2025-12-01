<?php
session_start();
require_once '../../auth/koneksi.php';

// HEADER ANTI-CACHE & ANTI-BUFFERING (WAJIB BUAT HOSTING)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx/Litespeed special header

// Matikan kompresi output
if(function_exists('apache_setenv')){ @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(1);

// Ambil Session
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
session_write_close(); // Tutup sesi agar tidak locking

// Kirim padding data agar browser langsung merender
echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$last_hash = null;
$start = time();

while (true) {
    // Auto kill script setelah 30 detik agar direfresh browser (Anti Timeout Hosting)
    if ((time() - $start) > 30) die();

    // QUERY YANG LEBIH AMAN
    // Kita ambil status 'pending' (Untuk Midtrans & Cash Pelanggan)
    $sql = "SELECT t.*, m.nomor_meja 
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            WHERE t.status_pembayaran = 'pending'";

    // Filter Cabang (Hanya jika bukan super admin mode global)
    if ($level != 'admin' || (isset($_GET['view_cabang']) && $_GET['view_cabang'] != 'pusat')) {
        $sql .= " AND m.cabang_id = '$cabang_id'";
    }
    
    $sql .= " ORDER BY t.created_at ASC";

    $result = $koneksi->query($sql);
    if (!$result) break;

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $trx_id = $row['id'];
        $items_res = $koneksi->query("SELECT d.qty, mn.nama_menu FROM transaksi_detail d JOIN menu mn ON d.menu_id = mn.id WHERE d.transaksi_id = '$trx_id'");
        $items = [];
        while($i = $items_res->fetch_assoc()) { $items[] = $i; }
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
    
    // Heartbeat (Detak Jantung) agar koneksi tidak diputus hosting
    echo ": keepalive\n\n";
    flush();
    
    if (connection_aborted()) break;
    sleep(2);
}
?>