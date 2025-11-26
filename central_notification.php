<?php
// File: central_notification.php

// 1. Daftar Alamat Aplikasi Tujuan (Webhook masing-masing aplikasi)
$destinations = [
    'RESTO' => 'https://penjualan.lopyta.com/Pemesanan/webhook_handler.php', 
    'LNDRY' => 'https://laundry.lopyta.com/api/midtrans_handler.php',
    'SHOP'  => 'https://toko.lopyta.com/callback/midtrans.php'
];

// 2. Ambil Data Mentah dari Midtrans
$json_input = file_get_contents('php://input');
$data = json_decode($json_input);

if (!$data) {
    http_response_code(400);
    die("Invalid JSON");
}

// 3. Ambil Order ID (Contoh: RESTO-123456)
$order_id = $data->order_id;

// 4. Pisahkan Prefix (Ambil kata sebelum tanda '-')
$parts = explode('-', $order_id);
$prefix = isset($parts[0]) ? strtoupper($parts[0]) : '';

// 5. Cek Tujuan dan Teruskan Data
if (array_key_exists($prefix, $destinations)) {
    $target_url = $destinations[$prefix];
    
    // Fungsi untuk meneruskan data (Forwarding)
    forwardNotification($target_url, $json_input);
    
    echo "Notification forwarded to " . $prefix;
} else {
    // Jika prefix tidak dikenali, catat error (log)
    error_log("Unknown Prefix for Order ID: " . $order_id);
    http_response_code(404);
    echo "Destination not found for prefix: " . $prefix;
}

// --- FUNGSI CURL UNTUK MENERUSKAN DATA ---
function forwardNotification($url, $payload) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
        'X-Forwarded-From: Central-Router' // Header tambahan sebagai penanda
    ]);
    
    // Eksekusi (kita tidak perlu menunggu respon detil, yang penting terkirim)
    // Tapi untuk debugging, bisa di log resultnya
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
?>