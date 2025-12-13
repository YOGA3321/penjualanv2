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
$last_data_hash = null;
$last_pending_count = -1;

while (true) {
    if ((time() - $start) > 40) die(); // Refresh connection every 40s

    $response_data = [];

    // --- FITUR A: HITUNG USER ONLINE (System Live - WAREHOUSE CONTEXT) ---
    $time_limit = date('Y-m-d H:i:s', time() - 120); 
    // User Guide: "berbeda dari global". Kita hitung admin & gudang saja, atau hanya yang sedang aktif di modul gudang?
    // Amannya: Admin + Gudang Users. Karyawan (kasir/dapur) tidak perlu dihitung di sini agar "beda".
    $sql_online = "SELECT COUNT(*) as online FROM users WHERE last_active > '$time_limit' AND level IN ('admin', 'gudang')"; 
    $res = $koneksi->query($sql_online);
    $response_data['online_users'] = ($res) ? $res->fetch_assoc()['online'] : 0;

    // --- FITUR B: LIST DATA PERMINTAAN MASUK (Full Data untuk Table) ---
    // Query Master Request
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
                // Pre-calc default kirim value logic
                $d['default_kirim'] = min($d['qty_minta'], $d['stok_gudang']);
                $items[] = $d;
            }
            $row['items'] = $items;
            $requests[] = $row;
        }
    }

    // Hash data request untuk efisiensi bandwidth (biar tidak kirim data besar kalau tidak berubah)
    $current_hash = md5(json_encode($requests));
    
    // Kirim full list jika ada perubahan data
    if ($current_hash !== $last_data_hash) {
        $response_data['request_list'] = $requests;
        $last_data_hash = $current_hash;
    }
    
    $response_data['pending_requests'] = $current_pending_count;

    // --- FITUR C: NOTIFIKASI TOAST BARU (One-time alert) ---
    // Logic: jika count bertambah dari loop sebelumnya
    if ($last_pending_count != -1 && $current_pending_count > $last_pending_count) {
        $diff = $current_pending_count - $last_pending_count;
        // Ambil info request terakhir
        $latest = $requests[count($requests)-1] ?? null; // Asumsi sort ASC, latest di bawah (wait, query ASC berarti latest di bawah)
        // Wait, query is ASC (FIFO). Latest created is at the bottom? Check created_at.
        // Pending logic might be tricky with mixed status. Better query strictly by ID desc for latest alert.
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
