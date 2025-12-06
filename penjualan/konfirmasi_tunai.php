<?php
session_start();
require_once '../auth/koneksi.php';

$uuid = $_GET['uuid'] ?? '';
if(empty($uuid)) { header("Location: index.php"); exit; }

// Ambil Data Transaksi
$q = $koneksi->query("SELECT t.*, m.nomor_meja, c.nama_cabang 
                      FROM transaksi t
                      JOIN meja m ON t.meja_id = m.id
                      JOIN cabang c ON m.cabang_id = c.id
                      WHERE t.uuid = '$uuid'");
$trx = $q->fetch_assoc();

if(!$trx) { die("Data tidak ditemukan"); }

// Jika sudah lunas, langsung lempar ke Sukses
if($trx['status_pembayaran'] == 'settlement') {
    header("Location: sukses.php?uuid=$uuid"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Poppins', sans-serif; }
        .card-confirm { max-width: 400px; width: 100%; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .dashed-line { border-bottom: 2px dashed #dee2e6; margin: 20px 0; }
        .animate-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="card card-confirm p-4 mx-auto bg-white">
        <div class="text-center mb-4">
            <div class="text-warning mb-3 animate-pulse">
                <i class="fas fa-cash-register fa-4x"></i>
            </div>
            <h4 class="fw-bold text-dark">Pesanan Diterima!</h4>
            <p class="text-muted small">Mohon menuju kasir untuk menyelesaikan pembayaran.</p>
        </div>

        <div class="bg-light p-3 rounded-4 mb-3">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Atas Nama</span>
                <span class="fw-bold text-dark"><?= htmlspecialchars($trx['nama_pelanggan']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Meja</span>
                <span class="badge bg-primary rounded-pill">No. <?= $trx['nomor_meja'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Total Tagihan</span>
                <span class="fw-bold text-primary fs-5">Rp <?= number_format($trx['total_harga']) ?></span>
            </div>
        </div>

        <div class="text-center">
            <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
            <small class="d-block text-muted fst-italic">Menunggu konfirmasi kasir...</small>
        </div>
    </div>
</div>

<script>
    // Cek status otomatis setiap 3 detik
    setInterval(() => {
        fetch('check_status_api.php?uuid=<?= $uuid ?>')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success' && data.data.status_pembayaran === 'settlement') {
                // Jika sudah lunas, pindah ke halaman Sukses
                window.location.href = 'sukses.php?uuid=<?= $uuid ?>';
            }
        });
    }, 3000);
</script>

</body>
</html>