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

// Jika status sudah settlement, lempar ke struk
if ($trx['status_pembayaran'] == 'settlement' || $trx['status_pesanan'] == 'diproses') {
    header("Location: struk?uuid=$uuid");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan - Modern Bites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .status-card { max-width: 400px; width: 90%; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.7; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

<div class="card status-card p-4 text-center bg-white">
    
    <?php if($trx['metode_pembayaran'] == 'tunai'): ?>
        <div class="mb-3 text-warning pulse"><i class="fas fa-cash-register fa-4x"></i></div>
        <h4 class="fw-bold">Menunggu Pembayaran</h4>
        <p class="text-muted mb-4">Silakan lakukan pembayaran di Kasir sebesar:</p>
        <h2 class="text-primary fw-bold mb-4">Rp <?= number_format($trx['total_harga']) ?></h2>
        <div class="alert alert-warning small">
            Sebutkan Meja <b>#<?= $trx['nomor_meja'] ?></b> kepada Kasir.
        </div>
    
    <?php else: ?>
        <div class="mb-3 text-primary pulse"><i class="fas fa-mobile-alt fa-4x"></i></div>
        <h4 class="fw-bold">Selesaikan Pembayaran</h4>
        <p class="text-muted mb-2">Pesanan Anda belum lunas.</p>
        <h2 class="text-primary fw-bold mb-4">Rp <?= number_format($trx['total_harga']) ?></h2>
        
        <button id="pay-button" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mb-3 shadow">
            <i class="fas fa-qrcode me-2"></i> Bayar Sekarang
        </button>
        <small class="text-muted d-block">Klik tombol di atas untuk membuka pembayaran.</small>
    <?php endif; ?>

    <hr>
    <a href="index" class="text-decoration-none text-secondary small">Kembali ke Menu Utama</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>

<script>
    const snapToken = '<?= $trx['snap_token'] ?>'; // Ambil token dari database
    
    // Event Tombol Bayar
    const btnPay = document.getElementById('pay-button');
    if(btnPay && snapToken) {
        btnPay.onclick = function() {
            window.snap.pay(snapToken, {
                onSuccess: function(result){ location.reload(); },
                onPending: function(result){ location.reload(); },
                onError: function(result){ Swal.fire('Error', 'Pembayaran gagal', 'error'); }
            });
        };
    }

    // Auto-Refresh Status (Polling)
    setInterval(() => {
        fetch('check_status_api?uuid=<?= $uuid ?>')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success' && data.data.status_pembayaran === 'settlement') {
                window.location.href = 'sukses?uuid=<?= $uuid ?>';
            }
        });
    }, 5000);
</script>

</body>
</html>