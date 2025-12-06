<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'pelanggan') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$uid = $_SESSION['user_id'];

// Ambil Data Transaksi User Ini
$sql = "SELECT t.*, c.nama_cabang 
        FROM transaksi t 
        LEFT JOIN meja m ON t.meja_id = m.id
        LEFT JOIN cabang c ON m.cabang_id = c.id
        WHERE t.user_id = '$uid' 
        ORDER BY t.created_at DESC";
$data = $koneksi->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }</style>
</head>
<body>

<div class="container py-4" style="max-width: 600px;">
    <a href="index.php" class="text-decoration-none text-muted mb-3 d-block fw-bold">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
    </a>

    <h4 class="fw-bold text-primary mb-4">Riwayat Jajan</h4>

    <?php if($data->num_rows > 0): ?>
        <?php while($row = $data->fetch_assoc()): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><?= $row['nama_cabang'] ?></h6>
                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></small>
                    </div>
                    <?php 
                        $st = $row['status_pembayaran'];
                        $bg = ($st=='settlement') ? 'bg-success' : (($st=='pending') ? 'bg-warning text-dark' : 'bg-danger');
                        $txt = ($st=='settlement') ? 'LUNAS' : strtoupper($st);
                    ?>
                    <span class="badge <?= $bg ?>"><?= $txt ?></span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h5 class="fw-bold text-primary mb-0">Rp <?= number_format($row['total_harga']) ?></h5>
                    
                    <?php if($st == 'settlement'): ?>
                        <a href="../penjualan/cetak_struk_pdf.php?uuid=<?= $row['uuid'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                            <i class="fas fa-file-pdf me-1"></i> Struk
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-receipt fa-3x mb-3 opacity-50"></i>
            <p>Belum ada riwayat pesanan.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>