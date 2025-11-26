<?php
session_start();
require_once '../../auth/koneksi.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Setup Buffer
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// 1. Ambil Data Session & Tutup Sesi
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
session_write_close(); // PENTING: Agar tidak loading terus

$last_hash = null;

while (true) {
    // 2. Query Data Menu (Sama seperti logic sebelumnya)
    $sql_menu = "SELECT m.*, k.nama_kategori, c.nama_cabang 
                 FROM menu m 
                 LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
                 LEFT JOIN cabang c ON m.cabang_id = c.id";

    if ($level != 'admin' || (isset($_GET['view_cabang']) && $_GET['view_cabang'] != 'pusat')) {
        // Jika view_cabang diset via JS, gunakan itu. Jika tidak, gunakan logic session
        // Kita filter agar tampil menu cabang + global
        $sql_menu .= " WHERE (m.cabang_id = '$cabang_id' OR m.cabang_id IS NULL)";
    } else {
        // Jika Admin mode global, tampilkan semua
        $sql_menu .= " WHERE 1=1";
    }
    
    $sql_menu .= " ORDER BY m.id DESC";
    
    $result = $koneksi->query($sql_menu);
    
    if (!$result) break; // Safety break

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // 3. Cek Perubahan
    $current_hash = md5(json_encode($data));

    if ($current_hash !== $last_hash) {
        echo "data: " . json_encode(['status' => 'success', 'data' => $data]) . "\n\n";
        $last_hash = $current_hash;
    }

    flush();
    if (connection_aborted()) break;
    sleep(2); // Update tiap 2 detik
}
?>