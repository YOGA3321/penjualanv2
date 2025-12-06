<?php
session_start();
require_once '../auth/koneksi.php';

$uuid = $_GET['uuid'] ?? '';
if(empty($uuid)) { header("Location: index.php"); exit; }

$q = $koneksi->query("SELECT * FROM transaksi WHERE uuid = '$uuid'");
$trx = $q->fetch_assoc();

if(!$trx) die("Transaksi tidak ditemukan");

// Jika Lunas -> Sukses
if($trx['status_pembayaran'] == 'settlement') {
    header("Location: sukses.php?uuid=$uuid"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body{background:#f8f9fa; display:flex; align-items:center; justify-content:center; min-height:100vh;}</style>
</head>
<body>

<div class="card border-0 shadow p-4 text-center rounded-4" style="max-width:400px; width:90%">
    <div class="text-primary mb-3"><i class="fas fa-qrcode fa-4x"></i></div>
    <h4 class="fw-bold">Belum Dibayar</h4>
    <p class="text-muted small mb-4">Selesaikan pembayaran QRIS Anda.</p>
    
    <h2 class="fw-bold text-dark mb-4">Rp <?= number_format($trx['total_harga']) ?></h2>
    
    <button id="pay-button" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mb-2">
        <i class="fas fa-wallet me-2"></i> Bayar Sekarang
    </button>
    <a href="index.php" class="btn btn-link text-secondary text-decoration-none">Kembali ke Menu</a>
</div>

<script>
    const payButton = document.getElementById('pay-button');
    const snapToken = '<?= $trx['snap_token'] ?>';

    payButton.onclick = function() {
        if(snapToken) {
            window.snap.pay(snapToken, {
                onSuccess: function(result){ window.location.href = 'sukses.php?uuid=<?= $uuid ?>'; },
                onPending: function(result){ location.reload(); },
                onError: function(result){ Swal.fire('Gagal', 'Pembayaran gagal', 'error'); },
                onClose: function(){ 
                    // Cek status ke server saat ditutup (siapa tau sudah bayar tapi close)
                    fetch('check_status_api.php?uuid=<?= $uuid ?>').then(r=>r.json()).then(d=>{
                        if(d.data.status_pembayaran === 'settlement') window.location.href = 'sukses.php?uuid=<?= $uuid ?>';
                    });
                }
            });
        } else {
            Swal.fire('Error', 'Token pembayaran kadaluarsa', 'error');
        }
    };
    
    // Auto check polling
    setInterval(() => {
        fetch('check_status_api.php?uuid=<?= $uuid ?>')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success' && data.data.status_pembayaran === 'settlement') {
                window.location.href = 'sukses.php?uuid=<?= $uuid ?>';
            }
        });
    }, 5000);
</script>

</body>
</html>