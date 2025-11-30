<?php
session_start();
require_once '../auth/koneksi.php';

$uuid = $_GET['uuid'] ?? '';
if(empty($uuid)) { header("Location: index.php"); exit; }

// Ambil Data Transaksi
$query = "SELECT t.*, m.nomor_meja, c.nama_cabang 
          FROM transaksi t
          JOIN meja m ON t.meja_id = m.id
          JOIN cabang c ON m.cabang_id = c.id
          WHERE t.uuid = '$uuid'";
$trx = $koneksi->query($query)->fetch_assoc();

if(!$trx) { die("Transaksi tidak ditemukan"); }

// Jika status sudah selesai/diproses/lunas, langsung lempar ke Struk
if ($trx['status_pembayaran'] == 'settlement' || $trx['status_pesanan'] == 'diproses') {
    header("Location: struk.php?uuid=$uuid");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menunggu Konfirmasi - Modern Bites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .status-card { max-width: 400px; width: 100%; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .pulse-icon { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.8; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

<div class="card status-card p-4 text-center">
    <div class="mb-4 text-warning pulse-icon">
        <i class="fas fa-hourglass-half fa-4x"></i>
    </div>
    
    <h4 class="fw-bold mb-2">Pesanan Diterima!</h4>
    
    <?php if($trx['metode_pembayaran'] == 'tunai'): ?>
        <p class="text-muted">
            Mohon lakukan pembayaran tunai sebesar <br>
            <strong class="text-primary fs-4">Rp <?= number_format($trx['total_harga']) ?></strong><br>
            ke Kasir untuk memproses pesanan Anda.
        </p>
        <div class="alert alert-warning small">
            <i class="fas fa-info-circle me-1"></i> Tunjukkan kode meja <strong>#<?= $trx['nomor_meja'] ?></strong> kepada Kasir.
        </div>
    <?php else: ?>
        <p class="text-muted">Menunggu konfirmasi pembayaran Online...</p>
    <?php endif; ?>

    <div class="progress mb-3" style="height: 5px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
    </div>
    
    <small class="text-muted d-block mb-3">Halaman ini akan otomatis update setelah pembayaran dikonfirmasi.</small>

    <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4">Kembali ke Menu</a>
</div>

<script>
    // Cek status setiap 3 detik
    setInterval(() => {
        fetch('check_status_api.php?uuid=<?= $uuid ?>')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success' && data.data.status_pembayaran === 'settlement') {
                // Jika sudah lunas, pindah ke struk
                window.location.href = 'struk.php?uuid=<?= $uuid ?>';
            }
        });
    }, 3000);
</script>

</body>
</html>