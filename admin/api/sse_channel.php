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

// 2. AMBIL PARAMETER
$filter_cabang = $_GET['cabang_id'] ?? ''; 
if($filter_cabang == 'pusat') $filter_cabang = '';

// Update status user yang sedang request (Admin/Karyawan)
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $koneksi->query("UPDATE users SET last_active = NOW() WHERE id = '$uid'");
}

session_write_close();

echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$start = time();

while (true) {
    if ((time() - $start) > 40) die(); 

    // 3. HITUNG USER ONLINE (STAFF ONLY)
    $time_limit = date('Y-m-d H:i:s', time() - 120); // Aktif 2 menit terakhir
    
    // [FIX] Tambahkan Filter: Hanya Admin & Karyawan (Pelanggan jangan dihitung)
    $sql = "SELECT COUNT(*) as online FROM users 
            WHERE last_active > '$time_limit' 
            AND level IN ('admin', 'karyawan')"; 
    
    // Filter Cabang (Jika sedang melihat cabang tertentu)
    if (!empty($filter_cabang)) {
        // Hitung Karyawan di cabang itu + Admin (Omnipresent)
        $sql .= " AND (cabang_id = '$filter_cabang' OR level = 'admin')";
    }

    $res = $koneksi->query($sql);
    $online = 0;
    if($res) {
        $row = $res->fetch_assoc();
        $online = $row['online'];
    }

    // 4. CEK NOTIFIKASI RESERVASI
    $now = date('Y-m-d H:i:s');
    $h_plus_15 = date('Y-m-d H:i:s', time() + (15 * 60));
    
    $sql_res = "SELECT r.waktu_reservasi, m.nomor_meja, u.nama as nama_pelanggan 
                FROM reservasi r
                JOIN meja m ON r.meja_id = m.id
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'pending' 
                AND r.waktu_reservasi BETWEEN '$now' AND '$h_plus_15'";
    
    if (!empty($filter_cabang)) {
        $sql_res .= " AND m.cabang_id = '$filter_cabang'";
    }
    
    $alerts = [];
    $res_query = $koneksi->query($sql_res);
    if ($res_query) {
        while($r = $res_query->fetch_assoc()) {
            $jam = date('H:i', strtotime($r['waktu_reservasi']));
            $alerts[] = "⚠️ Meja {$r['nomor_meja']} dipesan {$r['nama_pelanggan']} pukul $jam";
        }
    }

    // Kirim Data
    echo "data: " . json_encode(['online_users' => $online, 'reservasi_alert' => $alerts]) . "\n\n";
    flush();
    
    if (connection_aborted()) break;
    sleep(5);
}
?>