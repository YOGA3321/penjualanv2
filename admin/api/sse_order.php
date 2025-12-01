<?php
session_start();
require_once '../../auth/koneksi.php';

// 1. SETTING HEADER AGAR TIDAK DI-CACHE CLOUDFLARE/HOSTING
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // KHUSUS NGINX/LITESPEED (HOSTING)

// Matikan kompresi
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// 2. AMBIL SESI LALU TUTUP (AGAR TIDAK LOCKING)
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
session_write_close(); // PENTING: Tutup sesi biar halaman lain bisa dibuka

// 3. KIRIM "PANCINGAN" DATA KOSONG
// Beberapa hosting nunggu 2KB data dulu baru mau kirim ke browser
echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$last_hash = null;
$startTime = time();

// 4. LOOPING (BATASI WAKTU)
while (true) {
    // Jika koneksi sudah > 45 detik, matikan script.
    // Browser otomatis akan reconnect (Retry). 
    // Ini trik jitu menghindari Timeout 60s Cloudflare/Hosting.
    if ((time() - $startTime) > 45) {
        die(); 
    }

    $sql = "SELECT t.*, m.nomor_meja 
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            WHERE (t.status_pesanan = 'menunggu_konfirmasi' OR t.status_pesanan = 'menunggu_bayar')";

    if ($level != 'admin' || (isset($_GET['view_cabang']) && $_GET['view_cabang'] != 'pusat')) {
        $sql .= " AND m.cabang_id = '$cabang_id'";
    }
    
    $sql .= " ORDER BY t.created_at ASC";

    $result = $koneksi->query($sql);
    
    // Cek koneksi DB, jika putus reconnect (Opsional, mysqli biasanya handle sendiri)
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

    $current_hash = md5(json_encode($data));

    // Kirim data HANYA jika ada perubahan
    if ($current_hash !== $last_hash) {
        echo "data: " . json_encode(['status' => 'success', 'data' => $data]) . "\n\n";
        $last_hash = $current_hash;
        flush(); // Paksa kirim ke browser
    }
    
    // Kirim "Heartbeat" (Detak Jantung) agar koneksi tetap hidup
    // Hosting sering memutus koneksi yg diam saja
    echo ": keep-alive\n\n";
    flush();

    if (connection_aborted()) break;
    
    sleep(3); // Interval cek database (jangan terlalu cepat di hosting)
}
?>