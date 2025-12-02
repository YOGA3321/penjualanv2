<?php
session_start();
require_once '../../auth/koneksi.php';

// 1. HEADER SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); 

if(function_exists('apache_setenv')){ @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(1);

// 2. AMBIL PARAMETER & UPDATE STATUS ADMIN
$filter_cabang = $_GET['cabang_id'] ?? ''; 
if($filter_cabang == 'pusat') $filter_cabang = '';

// [PENTING] Update status user yang sedang request ini (Admin/Karyawan) agar terhitung online
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $koneksi->query("UPDATE users SET last_active = NOW() WHERE id = '$uid'");
}

session_write_close(); // Tutup sesi agar tidak memblokir request lain

echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$start = time();

while (true) {
    if ((time() - $start) > 40) die(); 

    // 3. HITUNG USER ONLINE (Aktif dalam 2 menit terakhir biar lebih toleran)
    $time_limit = date('Y-m-d H:i:s', time() - 120); 
    
    $sql = "SELECT COUNT(*) as online FROM users WHERE last_active > '$time_limit'";
    
    // LOGIKA BARU:
    if (!empty($filter_cabang)) {
        // Jika melihat cabang tertentu (Misal: Jogja), hitung:
        // 1. Karyawan cabang tersebut
        // 2. ATAU Admin (Karena Admin dianggap memantau semua cabang)
        $sql .= " AND (cabang_id = '$filter_cabang' OR level = 'admin')";
    }
    // Jika $filter_cabang kosong (Lihat Semua), dia akan menghitung SEMUA user tanpa syarat cabang.

    $res = $koneksi->query($sql);
    $online = 0;
    if($res) {
        $row = $res->fetch_assoc();
        $online = $row['online'];
    }

    echo "data: " . json_encode(['online_users' => $online]) . "\n\n";
    flush();
    
    if (connection_aborted()) break;
    sleep(5);
}
?>