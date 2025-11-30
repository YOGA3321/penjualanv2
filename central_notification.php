<?php
// FILE ROUTER TERPUSAT
// URL ini yang didaftarkan di Midtrans Dashboard > Settings > Notification URL

// Daftar Tujuan Webhook per Aplikasi
$routes = [
    // Jika Order ID diawali 'RESTO-', kirim ke folder penjualanv2
    'RESTO' => 'https://penjualan.lopyta.com/admin/api/midtrans_webhook.php',
    'LNDRY' => 'https://laundry.lopyta.com/api/midtrans_handler.php',
    'SHOP'  => 'https://toko.lopyta.com/callback.php'
];

// 1. Ambil Data Masuk
$json_input = file_get_contents('php://input');
$data = json_decode($json_input);

if (!$data) { http_response_code(400); die("Invalid JSON"); }

// 2. Baca Prefix (Contoh: RESTO-ax823-123123)
$order_id = $data->order_id;
$parts = explode('-', $order_id);
$prefix = strtoupper($parts[0]); // Ambil 'RESTO'

// 3. Router / Forwarding
if (array_key_exists($prefix, $routes)) {
    $destination = $routes[$prefix];

    // Kirim data ke tujuan menggunakan cURL
    $ch = curl_init($destination);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_input);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_input),
        'X-Forwarded-From: Central-Router' // Penanda keamanan opsional
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Teruskan respon dari aplikasi tujuan ke Midtrans
    http_response_code($httpCode);
    echo $response;
} else {
    // Prefix tidak dikenal
    error_log("Unknown Prefix: $prefix for Order: $order_id");
    http_response_code(404);
}
?>