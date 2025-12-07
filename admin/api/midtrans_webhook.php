<?php
// admin/api/midtrans_webhook.php
require_once '../../auth/koneksi.php'; // Sesuaikan path koneksi database Anda
require_once '../../vendor/autoload.php'; // Jika menggunakan library Midtrans PHP (Opsional jika pakai raw PHP)

// Konfigurasi Midtrans (Sesuaikan dengan Server Key Anda)
$serverKey = 'SB-Mid-server-XXXXXX'; // GANTI DENGAN SERVER KEY ANDA
$isProduction = false; // Ubah true jika sudah live

header('Content-Type: application/json');

// 1. Ambil Data JSON dari Midtrans (yang diteruskan Cloudflare)
$json_result = file_get_contents('php://input');
$notification = json_decode($json_result);

if (!$notification) {
    http_response_code(404);
    echo "Invalid JSON";
    exit();
}

// 2. Ambil variabel penting
$transaction = $notification->transaction_status;
$type = $notification->payment_type;
$order_id = $notification->order_id;
$fraud = $notification->fraud_status;

// 3. Cek Status Code (Validasi Keamanan Sederhana via Signature Key disarankan, tapi ini logika status dasarnya)
// Dokumentasi: https://docs.midtrans.com/en/after-payment/notification?id=transaction-status

$status_pembayaran = 'pending'; // Default

if ($transaction == 'capture') {
    if ($type == 'credit_card') {
        if ($fraud == 'challenge') {
            $status_pembayaran = 'pending';
        } else {
            $status_pembayaran = 'dibayar'; // Sukses Kartu Kredit
        }
    }
} else if ($transaction == 'settlement') {
    // INI YANG PALING PENTING (Gopay, QRIS, VA masuk sini)
    $status_pembayaran = 'dibayar';
} else if ($transaction == 'pending') {
    $status_pembayaran = 'pending';
} else if ($transaction == 'deny') {
    $status_pembayaran = 'gagal';
} else if ($transaction == 'expire') {
    $status_pembayaran = 'kadaluarsa';
} else if ($transaction == 'cancel') {
    $status_pembayaran = 'dibatalkan';
}

// 4. Update Database
// Kita cari transaksi berdasarkan midtrans_order_id atau order_id (tergantung kolom db Anda)
// Asumsi nama tabel 'transaksi' dan kolom 'midtrans_order_id'
$stmt = $conn->prepare("UPDATE transaksi SET status_pembayaran = ? WHERE midtrans_order_id = ?");
$stmt->bind_param("ss", $status_pembayaran, $order_id);
$execute = $stmt->execute();

if ($execute && $status_pembayaran == 'dibayar') {
    // 5. TRIGGER REAL-TIME UPDATE (SSE) [PENTING!]
    // Agar transaksi_masuk.php otomatis refresh tanpa reload page.
    // Kita buat file trigger sederhana.
    
    $data_sse = [
        'message' => 'Pembayaran Masuk!',
        'order_id' => $order_id,
        'status' => 'dibayar',
        'timestamp' => time()
    ];
    
    // Tulis ke file sementara yang dibaca oleh sse_channel.php
    // Pastikan folder ../tmp/ atau sejenisnya ada dan writable, atau simpan di DB tabel notifikasi
    file_put_contents('../../sse_trigger.txt', json_encode($data_sse));
    
    // Opsi Tambahan: Jika Anda menggunakan tabel notifikasi khusus
    // INSERT INTO notifikasi (...) VALUES (...)
    
    echo json_encode(['status' => 'success', 'message' => 'Database Updated & SSE Triggered']);
} else {
    echo json_encode(['status' => 'ok', 'message' => 'Status handled but no update required']);
}
?>