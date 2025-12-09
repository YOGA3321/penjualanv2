<?php
// FILE: api/sse_notifications.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../auth/koneksi.php';

// Fungsi untuk mengirim data SSE
function sendMsg($id, $msg) {
    echo "id: $id" . PHP_EOL;
    echo "data: $msg" . PHP_EOL;
    echo PHP_EOL;
    ob_flush();
    flush();
}

// Loop Tak Terbatas untuk Cek Database
// Note: Di production, sebaiknya pakai interval polling JS daripada long-polling PHP loop jika server terbatas.
// Tapi untuk demo Real-time, kita pakai loop dengan sleep.
$last_count = -1;

// Batas waktu eksekusi skrip (misal 30 detik agar tidak timeout gateway) -> Klien akan reconnect otomatis
$start_time = time();
while (true) {
    if (time() - $start_time > 25) break; // Reconnect tiap 25 detik

    // Cek Request Pending
    $q = $koneksi->query("SELECT COUNT(*) as total FROM request_stok WHERE status='pending'");
    $current_count = $q->fetch_assoc()['total'];

    if ($last_count == -1) {
        // Initials
        $last_count = $current_count;
    } 
    elseif ($current_count > $last_count) {
        $diff = $current_count - $last_count;
        $data = json_encode([
            'title' => 'Permintaan Baru!',
            'message' => "Ada $diff permintaan stok baru dari cabang.",
            'total' => $current_count
        ]);
        sendMsg(time(), $data);
        $last_count = $current_count;
    }
    
    // Kurangi load CPU
    sleep(3);
    
    // Cek koneksi klien putus
    if (connection_aborted()) break;
}
?>
