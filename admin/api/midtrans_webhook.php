<?php
// Lokasi: admin/api/midtrans_webhook.php

// 1. Load Koneksi Database
// Sesuaikan path ini dengan struktur folder Anda
require_once '../../auth/koneksi.php'; 

// Jika menggunakan library Midtrans, load autoloader (opsional jika manual)
// require_once dirname(__FILE__) . '/../../vendor/autoload.php';

// Set Header agar response berupa JSON
header('Content-Type: application/json');

// 2. Ambil Raw Body dari Cloudflare / Midtrans
$json_result = file_get_contents('php://input');
$notification = json_decode($json_result);

// Validasi jika data kosong
if (!$notification) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit();
}

// 3. Ambil Data Penting
$transaction_status = $notification->transaction_status;
$payment_type       = $notification->payment_type;
$order_id           = $notification->order_id;
$fraud_status       = $notification->fraud_status;

// 4. Logika Penentuan Status Pembayaran
$status_transaksi = 'pending';

if ($transaction_status == 'capture') {
    if ($payment_type == 'credit_card') {
        if ($fraud_status == 'challenge') {
            $status_transaksi = 'pending';
        } else {
            $status_transaksi = 'dibayar';
        }
    }
} else if ($transaction_status == 'settlement') {
    // Status Paling Penting: Uang sudah masuk (Gopay, VA, QRIS)
    $status_transaksi = 'dibayar';
} else if ($transaction_status == 'pending') {
    $status_transaksi = 'pending';
} else if ($transaction_status == 'deny') {
    $status_transaksi = 'gagal';
} else if ($transaction_status == 'expire') {
    $status_transaksi = 'kadaluarsa';
} else if ($transaction_status == 'cancel') {
    $status_transaksi = 'dibatalkan';
}

// 5. Update Database
// Asumsi tabel bernama 'transaksi' dan kolom order id bernama 'midtrans_order_id'
// Sesuaikan query ini dengan nama tabel Anda di 'penjualan2.sql'
$sql = "UPDATE transaksi SET status_pembayaran = ? WHERE midtrans_order_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $status_transaksi, $order_id);
    $execute = $stmt->execute();
    $stmt->close();

    if ($execute && $status_transaksi == 'dibayar') {
        // --- LOGIKA REAL-TIME (SSE TRIGGER) ---
        // Kita buat file sementara (.txt) yang berisi data notifikasi
        // File ini akan dibaca oleh sse_channel.php
        
        $data_notif = [
            'order_id' => $order_id,
            'status'   => 'dibayar',
            'waktu'    => date('H:i:s'),
            'pesan'    => 'Pesanan Baru Masuk: ' . $order_id
        ];

        // Simpan ke file di folder yang sama (pastikan permission folder writeable)
        file_put_contents('sse_trigger.txt', json_encode($data_notif));

        echo json_encode(['status' => 'success', 'message' => 'Status Updated & SSE Triggered']);
    } else {
        echo json_encode(['status' => 'ok', 'message' => 'Status Updated (Not Paid Yet)']);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $conn->error]);
}
?>