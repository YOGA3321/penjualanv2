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

// Update status user active
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $koneksi->query("UPDATE users SET last_active = NOW() WHERE id = '$uid'");
}

session_write_close();

echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$start = time();
$last_pending_count = -1; // Track perubahan

while (true) {
    if ((time() - $start) > 40) die(); // Refresh connection every 40s

    $response_data = [];

    // --- FITUR A: HITUNG USER ONLINE (System Live) ---
    $time_limit = date('Y-m-d H:i:s', time() - 120); 
    $sql_online = "SELECT COUNT(*) as online FROM users WHERE last_active > '$time_limit'"; 
    $res = $koneksi->query($sql_online);
    $response_data['online_users'] = ($res) ? $res->fetch_assoc()['online'] : 0;

    // --- FITUR B: CEK PERMINTAAN STOK BARU & KIRIM NOTIFIKASI ---
    $sql_req = "SELECT COUNT(*) as pending_count FROM request_stok WHERE status = 'pending'";
    $res_req = $koneksi->query($sql_req);
    $pending_count = ($res_req) ? $res_req->fetch_assoc()['pending_count'] : 0;
    
    $response_data['pending_requests'] = $pending_count;

    // Deteksi perubahan (request baru masuk)
    if ($last_pending_count == -1) {
        // Inisialisasi pertama kali
        $last_pending_count = $pending_count;
    } elseif ($pending_count > $last_pending_count) {
        // Ada request baru!
        $diff = $pending_count - $last_pending_count;
        
        // Ambil detail request terbaru untuk notifikasi
        $sql_latest = "SELECT r.kode_request, c.nama_cabang 
                       FROM request_stok r 
                       JOIN cabang c ON r.cabang_id = c.id 
                       WHERE r.status = 'pending' 
                       ORDER BY r.created_at DESC 
                       LIMIT 1";
        $res_latest = $koneksi->query($sql_latest);
        
        if ($res_latest && $res_latest->num_rows > 0) {
            $latest = $res_latest->fetch_assoc();
            $response_data['new_request_alert'] = [
                'title' => 'Permintaan Stok Baru!',
                'message' => "Request {$latest['kode_request']} dari {$latest['nama_cabang']}",
                'kode' => $latest['kode_request'],
                'count' => $diff
            ];
        }
        
        $last_pending_count = $pending_count;
    } elseif ($pending_count < $last_pending_count) {
        // Request berkurang (diproses/dikirim)
        $last_pending_count = $pending_count;
    }

    // Kirim Data JSON
    echo "data: " . json_encode($response_data) . "\n\n";
    flush();
    
    if (connection_aborted()) break;
    
    sleep(3); 
}
?>
