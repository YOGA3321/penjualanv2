<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'pelanggan') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$uid = $_SESSION['user_id'];

// Ambil Transaksi
$sql = "SELECT t.*, m.nomor_meja, c.nama_cabang 
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
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .item-list { background: #fff; border-radius: 8px; padding: 15px; margin-top: 10px; border: 1px solid #eee; }
        .item-row { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.9rem; color: #666; margin-bottom: 5px; }
        .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem; color: #6366f1; border-top: 1px dashed #ddd; padding-top: 10px; margin-top: 10px; }
    </style>
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
                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?> &bull; Meja <?= $row['nomor_meja'] ?></small>
                    </div>
                    <?php 
                        $st = $row['status_pembayaran'];
                        $bg = ($st=='settlement') ? 'bg-success' : (($st=='pending') ? 'bg-warning text-dark' : 'bg-danger');
                        $txt = ($st=='settlement') ? 'LUNAS' : strtoupper($st);
                    ?>
                    <span class="badge <?= $bg ?>"><?= $txt ?></span>
                </div>

                <div class="item-list">
                    <?php
                        $trx_id = $row['id'];
                        $q_detail = $koneksi->query("SELECT d.*, m.nama_menu FROM transaksi_detail d JOIN menu m ON d.menu_id = m.id WHERE d.transaksi_id = '$trx_id'");
                        $subtotal_murni = 0;
                        while($item = $q_detail->fetch_assoc()):
                            $subtotal_murni += $item['subtotal'];
                    ?>
                        <div class="item-row">
                            <span><?= $item['qty'] ?>x <?= $item['nama_menu'] ?></span>
                            <span>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="mt-3 px-2">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rp <?= number_format($subtotal_murni, 0, ',', '.') ?></span>
                    </div>
                    
                    <?php if($row['diskon'] > 0): ?>
                    <div class="summary-row text-success">
                        <span>Diskon / Voucher</span>
                        <span>- Rp <?= number_format($row['diskon'], 0, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="total-row">
                        <span>Total Bayar</span>
                        <span>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <?php if($st == 'settlement'): ?>
                        <a href="../penjualan/cetak_struk_pdf.php?uuid=<?= $row['uuid'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i> Download Struk
                        </a>
                    <?php elseif($st == 'pending' && $row['metode_pembayaran'] == 'midtrans'): ?>
                        <a href="../penjualan/status.php?uuid=<?= $row['uuid'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                            Bayar Sekarang
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