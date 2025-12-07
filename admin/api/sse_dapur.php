<?php
// Header Wajib SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx Config (Optional tapi bagus)

// Matikan Output Buffering & Compression
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(1);

session_start();
require_once '../../auth/koneksi.php';

// Ambil Data Session Dulu
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';

// [PENTING] TUTUP SESI AGAR TIDAK LOCKING
session_write_close(); 

// Filter Cabang (Dari GET atau Session)
$view_cabang = $_GET['view_cabang'] ?? 'pusat';
$where_cabang = "";

if ($level == 'admin' && $view_cabang != 'pusat') {
    $where_cabang = " AND m.cabang_id = '$view_cabang' ";
} elseif ($level != 'admin') {
    $where_cabang = " AND m.cabang_id = '$cabang_id' ";
}

// State Awal
$last_data_hash = null;

// Loop Infinite
while (true) {
    // Cek Koneksi Database (Reconnect jika putus - opsional, tapi bagus utk long running process)
    if (!$koneksi->ping()) {
        $koneksi->close();
        $koneksi->connect($servername, $username, $password, $dbname);
    }

    // Query Pesanan "Diproses" (Sedang Masak)
    // Status Pembayaran harus Settlement (Lunas) atau Capture (Midtrans)
    $sql = "SELECT t.id, t.nama_pelanggan, t.created_at, m.nomor_meja, c.nama_cabang
            FROM transaksi t
            JOIN meja m ON t.meja_id = m.id
            JOIN cabang c ON m.cabang_id = c.id
            WHERE t.status_pesanan = 'diproses' 
            AND t.status_pembayaran IN ('settlement', 'capture')
            $where_cabang
            ORDER BY t.created_at ASC";

    $result = $koneksi->query($sql);
    
    if ($result) {
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $trx_id = $row['id'];
            
            // Ambil Detail Menu
            $details = $koneksi->query("SELECT d.qty, d.catatan, mn.nama_menu 
                                        FROM transaksi_detail d 
                                        JOIN menu mn ON d.menu_id = mn.id 
                                        WHERE d.transaksi_id = '$trx_id'");
            $items = [];
            while ($d = $details->fetch_assoc()) {
                $items[] = $d;
            }
            $row['items'] = $items;
            $orders[] = $row;
        }

        $current_hash = md5(json_encode($orders));

        // Kirim Data Hanya Jika Berubah (Hemat Bandwidth)
        if ($current_hash !== $last_data_hash) {
            echo "data: " . json_encode($orders) . "\n\n";
            flush(); // Dorong data ke browser
            $last_data_hash = $current_hash;
        }
    } else {
        // Kirim array kosong jika error/kosong agar loading hilang
        echo "data: []\n\n";
        flush();
    }

    // Jeda 2 detik sebelum cek lagi
    sleep(2);
}
?>