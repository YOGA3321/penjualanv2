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

// Ambil ID User yang sedang login
$my_id = $_SESSION['user_id'] ?? 0;

// TUTUP SESI AGAR TIDAK LOCKING (PENTING!)
session_write_close();

if ($my_id == 0) {
    echo "data: " . json_encode(['status' => 'error', 'msg' => 'Not logged in']) . "\n\n";
    flush();
    exit;
}

while (true) {
    $now = date("Y-m-d H:i:s");

    // 1. Update 'last_active' user ini (Tanda bahwa dia masih online)
    $koneksi->query("UPDATE users SET last_active = '$now' WHERE id = '$my_id'");

    // 2. Hitung User Online (Yang aktif dalam 10 detik terakhir)
    // Kita anggap user offline jika tidak ada koneksi selama 10 detik
    $q_online = $koneksi->query("SELECT COUNT(*) as total FROM users WHERE last_active > NOW() - INTERVAL 10 SECOND");
    $row_online = $q_online->fetch_assoc();
    $total_online = $row_online['total'];

    // 3. Kirim Data ke Browser
    $payload = [
        'status' => 'connected',
        'online_users' => $total_online,
        'timestamp' => time()
    ];

    echo "data: " . json_encode($payload) . "\n\n";
    flush();

    // Cek jika klien putus
    if (connection_aborted()) break;

    sleep(3); // Update setiap 3 detik (Hemat resource)
}
?>