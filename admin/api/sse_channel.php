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

// Filter Module (admin, gudang, homepage, kasir, dll)
$current_module = $_GET['module'] ?? 'unknown';

// Update status user yang sedang request
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // Update last_active AND last_module
    $stmt = $koneksi->prepare("UPDATE users SET last_active = NOW(), last_module = ? WHERE id = ?");
    $stmt->bind_param("si", $current_module, $uid);
    $stmt->execute();
} else {
    // No Session
}

session_write_close();

echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

// --- SETUP FILE TRIGGER PEMBAYARAN ---
$trigger_file = '../../sse_trigger.txt'; 
$last_trigger_time = file_exists($trigger_file) ? filemtime($trigger_file) : 0;
// -------------------------------------

$start = time();

while (true) {
    if ((time() - $start) > 40) die(); 

    $response_data = [];

    // --- FITUR A: HITUNG USER ONLINE PER MODULE ---
    $time_limit = date('Y-m-d H:i:s', time() - 120); 
    
    // Default logic: Count users in the SAME module (or specific logic)
    // User requested: "ketika admin berpindah ke gudang maka pada admin akan berkurang"
    // So we count users where last_module = 'admin' OR 'gudang' separately?
    // Or just return the count relevant to the viewer? 
    // Let's return the count of users in the CURRENT module being viewed.
    
    $sql_count = "SELECT COUNT(*) as online FROM users WHERE last_active > '$time_limit'";
    
    if ($current_module == 'admin') {
         // Show users currently in Admin Module
         $sql_count .= " AND last_module = 'admin'";
    } elseif ($current_module == 'gudang') {
         // Show users currently in Gudang Module
         $sql_count .= " AND last_module = 'gudang'";
    } else {
         // Fallback / Homepage: Show total logged in users
         // $sql_count .= ""; // All active users
    }

    if (!empty($filter_cabang)) {
        $sql_count .= " AND (cabang_id = '$filter_cabang' OR level = 'admin')";
    }

    $res = $koneksi->query($sql_count);
    $response_data['online_users'] = ($res) ? $res->fetch_assoc()['online'] : 0;

    // --- FITUR B: CEK NOTIFIKASI RESERVASI ---
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
    $response_data['reservasi_alert'] = $alerts;

    // --- FITUR C: DETEKSI PEMBAYARAN MASUK (WEBHOOK) ---
    clearstatcache();
    if (file_exists($trigger_file)) {
        $current_file_time = filemtime($trigger_file);
        if ($current_file_time > $last_trigger_time) {
            $last_trigger_time = $current_file_time;
            
            // Baca isi file trigger
            $trigger_content = file_get_contents($trigger_file);
            $response_data['payment_event'] = json_decode($trigger_content);
        }
    }

    // --- FITUR D: STATUS REQUEST STOK (Realtime Update) ---
    // Cek perubahan status request untuk cabang ini (hanya jika ada filter cabang)
    if (!empty($filter_cabang)) {
        // [FIX] Sort Pending/Dikirim first, then by Date
        $sql_req = "SELECT id, kode_request, status, created_at 
                    FROM request_stok 
                    WHERE cabang_id = '$filter_cabang' 
                    ORDER BY CASE WHEN status IN ('pending', 'dikirim') THEN 0 ELSE 1 END ASC, created_at DESC 
                    LIMIT 20";
        $res_req = $koneksi->query($sql_req);
        $req_data = [];
        if ($res_req) {
            while($r = $res_req->fetch_assoc()) {
                $req_data[] = $r;
            }
        }
        
        $req_hash = md5(json_encode($req_data));
        
        // Simpan hash di static variables (simulasi dengan session/temp file jika perlu, tapi untuk loop ini pakai var luar loop)
        // Note: $last_req_hash harus didefinisikan di luar loop
        if (!isset($last_req_hash) || $req_hash !== $last_req_hash) {
            $response_data['request_history'] = $req_data;
            if (isset($last_req_hash)) { // Cuma kirim alert alert jika bukan first load
                 // Optional: $response_data['request_alert'] = "Status Request Berubah!";
            }
            $last_req_hash = $req_hash;
        }
    }

    // Kirim Data JSON
    echo "data: " . json_encode($response_data) . "\n\n";
    flush();
    
    if (connection_aborted()) break;
    
    // Interval lebih cepat (3 detik) agar notifikasi pembayaran terasa responsif
    sleep(3); 
}
?>