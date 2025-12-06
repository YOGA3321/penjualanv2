<?php
// File: admin/api/sse_reservasi.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../../auth/koneksi.php';

// [FIX LOCALHOST] Tutup sesi agar tidak mengunci browser (session locking)
session_write_close();

// Matikan buffer/kompresi agar stream lancar
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// Fungsi untuk mendapatkan checksum state reservasi saat ini
function getReservasiState($koneksi) {
    // Kita cek jumlah baris dan ID terakhir untuk mendeteksi insert baru
    // Kita juga cek 'updated_at' (jika ada) atau status change
    // Query ini ringan agar server tidak berat
    $sql = "SELECT COUNT(*) as total, MAX(id) as last_id, 
            (SELECT COUNT(*) FROM reservasi WHERE status='pending') as pending_count 
            FROM reservasi";
            
    $result = $koneksi->query($sql);
    if($result) {
        return json_encode($result->fetch_assoc());
    }
    return false;
}

$lastState = getReservasiState($koneksi);

// Loop Infinite untuk Realtime
while (true) {
    $currentState = getReservasiState($koneksi);
    
    // Jika ada perubahan data dibanding state sebelumnya
    if ($currentState && $currentState !== $lastState) {
        $lastState = $currentState;
        
        // Kirim event ke browser
        echo "data: update_reservasi\n\n";
        flush();
    }
    
    // Jika klien putus koneksi, hentikan script PHP
    if (connection_aborted()) break;
    
    // Istirahat 2 detik sebelum cek lagi (mengurangi beban CPU localhost)
    sleep(2);
}
?>