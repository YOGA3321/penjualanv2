<?php
session_start();
if(empty($_GET['uuid'])) header("Location: index.php");
$uuid = $_GET['uuid'];

// Cek User Login
$dashboard_url = "index.php"; // Default tamu
if(isset($_SESSION['user_id']) && $_SESSION['level'] == 'pelanggan') {
    $dashboard_url = "../pelanggan/index.php"; // Ke Dashboard Pelanggan
}
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
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: sans-serif; }
        .card-success { max-width: 400px; width: 90%; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="card card-success p-5 text-center bg-white">
        <div class="mb-4 text-success">
            <i class="fas fa-check-circle fa-5x"></i>
        </div>
        <h3 class="fw-bold mb-2">Pembayaran Sukses!</h3>
        <p class="text-muted mb-4">Terima kasih telah memesan.</p>

        <div class="d-grid gap-2">
            <a href="cetak_struk_pdf.php?uuid=<?= $uuid ?>" class="btn btn-danger fw-bold rounded-pill" target="_blank">
                <i class="fas fa-file-pdf me-2"></i> Download Struk
            </a>
            <a href="<?= $dashboard_url ?>" class="btn btn-outline-primary fw-bold rounded-pill">
                <i class="fas fa-home me-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>