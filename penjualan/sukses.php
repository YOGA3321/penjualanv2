<?php
session_start();
if(empty($_GET['uuid'])) header("Location: index.php");
$uuid = $_GET['uuid'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berhasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card-success { max-width: 400px; width: 100%; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="card card-success p-4 text-center">
        <div class="mb-3 text-success">
            <i class="fas fa-check-circle fa-5x"></i>
        </div>
        <h4 class="fw-bold mb-1">Pembayaran Berhasil!</h4>
        <p class="text-muted small mb-4">Pesanan Anda sedang disiapkan di dapur.</p>

        <div class="d-grid gap-2">
            <a href="cetak_struk_pdf.php?uuid=<?= $uuid ?>" class="btn btn-outline-danger fw-bold py-2 rounded-pill">
                <i class="fas fa-file-pdf me-2"></i> Download Struk (PDF)
            </a>

            <a href="index.php" class="btn btn-primary fw-bold py-2 rounded-pill">
                <i class="fas fa-utensils me-2"></i> Pesan Lagi / Kembali ke Menu
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Tampilkan Notif Sukses saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'success',
                title: 'Transaksi Selesai!',
                text: 'Terima kasih telah memesan.',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>
</body>
</html>