<?php
// Header SSE Wajib
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx fix

// Matikan Buffer PHP
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(1);

session_start();
require_once '../../auth/koneksi.php';

// Tutup sesi agar browser lain tidak macet
session_write_close(); 

$view_cabang = $_GET['view_cabang'] ?? 'pusat';
$search_kw = $_GET['search'] ?? '';
$last_hash = null;

while (true) {
    // Reconnect DB jika putus (untuk long polling)
    if (!$koneksi->ping()) {
        $koneksi->close();
        $koneksi->connect($servername, $username, $password, $dbname);
    }

    // QUERY: Ambil semua yang belum 'selesai' dan belum 'cancel'
    $where_sql = "WHERE t.status_pesanan IN ('menunggu_konfirmasi', 'menunggu_bayar', 'diproses', 'siap_saji')
                  AND t.status_pembayaran NOT IN ('cancel', 'expire', 'failure')";

    if ($view_cabang != 'pusat') $where_sql .= " AND m.cabang_id = '$view_cabang'";
    if (!empty($search_kw)) {
        $kw = $koneksi->real_escape_string($search_kw);
        $where_sql .= " AND (t.nama_pelanggan LIKE '%$kw%' OR t.uuid LIKE '%$kw%') ";
    }

    $sql = "SELECT t.id, t.uuid, t.nama_pelanggan, t.total_harga, t.created_at, 
                   t.status_pesanan, t.status_pembayaran, t.metode_pembayaran, t.diskon,
                   m.nomor_meja, c.nama_cabang 
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            JOIN cabang c ON m.cabang_id = c.id
            $where_sql
            ORDER BY 
                CASE t.status_pesanan 
                    WHEN 'siap_saji' THEN 1
                    WHEN 'menunggu_konfirmasi' THEN 2
                    WHEN 'menunggu_bayar' THEN 3
                    WHEN 'diproses' THEN 4
                    ELSE 5 
                END ASC,
                t.created_at ASC";

    $result = $koneksi->query($sql);
    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $trx_id = $row['id'];
            // Ambil items
            $items_res = $koneksi->query("SELECT d.qty, m.nama_menu, d.catatan FROM transaksi_detail d JOIN menu m ON d.menu_id = m.id WHERE d.transaksi_id = '$trx_id' LIMIT 3");
            $items = [];
            while($i = $items_res->fetch_assoc()) { $items[] = $i; }
            $row['items'] = $items;
            
            $cnt = $koneksi->query("SELECT COUNT(*) as c FROM transaksi_detail WHERE transaksi_id='$trx_id'")->fetch_assoc()['c'];
            $row['more_items'] = max(0, $cnt - 3);

            $data[] = $row;
        }
    }

    // Hash data untuk cek perubahan
    $current_hash = md5(json_encode($data));

    if ($current_hash !== $last_hash) {
        echo "data: " . json_encode($data) . "\n\n";
        flush();
        $last_hash = $current_hash;
    } else {
        // Ping (agar koneksi tidak timeout)
        echo ": keepalive\n\n";
        flush();
    }

    // Cek setiap 2 detik
    sleep(2);
}
?>