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

$current_module = $_GET['module'] ?? 'unknown';
$uid = null;

// Ambil ID user dari session
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    // Update awal (init)
    $stmt = $koneksi->prepare("UPDATE users SET last_active = NOW(), last_module = ? WHERE id = ?");
    $stmt->bind_param("si", $current_module, $uid);
    $stmt->execute();
}

session_write_close();

echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

$start = time();
$last_data_hash = null;
$last_pending_count = -1;

while (true) {
    if ((time() - $start) > 40) die(); // Refresh connection every 40s

    // --- [FIX] HEARTBEAT: Jaga User Tetap Online ---
    if ($uid) {
        $koneksi->query("UPDATE users SET last_active = NOW() WHERE id = '$uid'");
    }

    $response_data = [];

    // --- FITUR A: HITUNG USER ONLINE ---
    $time_limit = date('Y-m-d H:i:s', time() - 120); 
    
    $sql_online = "SELECT COUNT(*) as online FROM users WHERE last_active > '$time_limit'";
    
    if ($current_module == 'gudang') {
         $sql_online .= " AND last_module = 'gudang'";
    } elseif ($current_module == 'admin') {
         $sql_online .= " AND last_module = 'admin'";
    }
    
    $res = $koneksi->query($sql_online);
    $response_data['online_users'] = ($res) ? $res->fetch_assoc()['online'] : 0;

    // --- FITUR B: LIST DATA PERMINTAAN MASUK ---
    $sql = "SELECT r.*, c.nama_cabang, u.nama as nama_user 
            FROM request_stok r 
            JOIN cabang c ON r.cabang_id = c.id
            JOIN users u ON r.user_id = u.id
            WHERE r.status IN ('pending', 'diproses')
            ORDER BY r.created_at ASC";
    
    $req_res = $koneksi->query($sql);
    $requests = [];
    $current_pending_count = 0;

    if($req_res) {
        while($row = $req_res->fetch_assoc()) {
            if($row['status'] == 'pending') $current_pending_count++;
            
            $req_id = $row['id'];
            // Ambil Detail Items
            $details = $koneksi->query("SELECT rd.*, g.nama_item, g.stok as stok_gudang, g.satuan 
                                        FROM request_detail rd 
                                        JOIN gudang_items g ON rd.item_id = g.id 
                                        WHERE rd.request_id='$req_id'");
            $items = [];
            while($d = $details->fetch_assoc()) {
                $d['default_kirim'] = min($d['qty_minta'], $d['stok_gudang']);
                $items[] = $d;
            }
            $row['items'] = $items;
            $requests[] = $row;
        }
    }

    // Hash data request
    $current_hash = md5(json_encode($requests));
    
    if ($current_hash !== $last_data_hash) {
        $response_data['request_list'] = $requests;
        $last_data_hash = $current_hash;
    }
    
    $response_data['pending_requests'] = $current_pending_count;

    // --- FITUR C: NOTIFIKASI TOAST BARU ---
    if ($last_pending_count != -1 && $current_pending_count > $last_pending_count) {
        $diff = $current_pending_count - $last_pending_count;
        $sql_latest = "SELECT r.kode_request, c.nama_cabang FROM request_stok r JOIN cabang c ON r.cabang_id=c.id WHERE status='pending' ORDER BY r.id DESC LIMIT 1";
        $l_res = $koneksi->query($sql_latest);
        if($l_res && $l_res->num_rows > 0) {
            $l_row = $l_res->fetch_assoc();
            $response_data['new_request_alert'] = [
                'title' => 'Permintaan Stok Baru!',
                'message' => "Request {$l_row['kode_request']} dari {$l_row['nama_cabang']}",
                'count' => $diff
            ];
        }
    }
    $last_pending_count = $current_pending_count;

    // Kirim Data JSON
    echo "data: " . json_encode($response_data) . "\n\n";
    flush();
    
    if (connection_aborted()) break;
    
    sleep(3); 
}
?>