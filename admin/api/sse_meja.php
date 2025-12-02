<?php
session_start();
require_once '../../auth/koneksi.php';

// Header Anti-Buffering (Wajib untuk Hosting)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); 

// Matikan Kompresi
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(1);

// Ambil Parameter
$cabang_id = $_GET['cabang_id'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 24; // Tampilkan 24 meja per halaman (Ringan)
$offset = ($page - 1) * $limit;

session_write_close();

// Pancingan Data
echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$last_hash = null;
$start = time();

while (true) {
    if ((time() - $start) > 30) die();

    // 1. HITUNG TOTAL DATA (Untuk Pagination)
    $sql_count = "SELECT COUNT(*) as total FROM meja m";
    if (!empty($cabang_id)) {
        $sql_count .= " WHERE m.cabang_id = '$cabang_id'";
    }
    $total_data = $koneksi->query($sql_count)->fetch_assoc()['total'];
    $total_pages = ceil($total_data / $limit);

    // 2. AMBIL DATA SESUAI HALAMAN (LIMIT)
    $sql = "SELECT m.*, c.nama_cabang 
            FROM meja m 
            LEFT JOIN cabang c ON m.cabang_id = c.id";
            
    if (!empty($cabang_id)) {
        $sql .= " WHERE m.cabang_id = '$cabang_id'";
    }
    
    // Sorting: Cabang dulu, lalu Nomor Meja (Angka)
    $sql .= " ORDER BY c.id ASC, CAST(m.nomor_meja AS UNSIGNED) ASC 
              LIMIT $offset, $limit";

    $result = $koneksi->query($sql);
    
    if (!$result) break;

    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Buat URL QR Code disini agar JS tidak repot
        $row['qr_url'] = BASE_URL . "/penjualan/index.php?token=" . $row['qr_token'];
        $data[] = $row;
    }

    // Bungkus Data + Info Halaman
    $response = [
        'status' => 'success',
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_data' => $total_data
        ]
    ];

    $json = json_encode($response);
    $hash = md5($json);

    if ($hash !== $last_hash) {
        echo "data: {$json}\n\n";
        $last_hash = $hash;
        flush();
    }
    
    echo ": keepalive\n\n";
    flush();
    
    if (connection_aborted()) break;
    sleep(3);
}
?>