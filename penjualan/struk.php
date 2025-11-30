<?php
require_once '../auth/koneksi.php';
$uuid = $_GET['uuid'] ?? '';

if(empty($uuid)) { header("Location: index.php"); exit; }

// 1. AMBIL DATA DULU (FIX ERROR Undefined variable $trx)
$query = "SELECT t.*, m.nomor_meja, c.nama_cabang, c.alamat 
          FROM transaksi t 
          JOIN meja m ON t.meja_id = m.id
          JOIN cabang c ON m.cabang_id = c.id
          WHERE t.uuid = '$uuid'";
$trx = $koneksi->query($query)->fetch_assoc();

// Cek jika data tidak ada
if (!$trx) { die("Data transaksi tidak ditemukan."); }

// 2. BARU LAKUKAN PENGECEKAN KEAMANAN
// Jika status masih pending dan metode tunai, LEMPAR BALIK ke halaman tunggu (status.php)
if ($trx['metode_pembayaran'] == 'tunai' && $trx['status_pembayaran'] == 'pending') {
    header("Location: status.php?uuid=$uuid");
    exit;
}

$details = $koneksi->query("SELECT d.*, m.nama_menu FROM transaksi_detail d JOIN menu m ON d.menu_id = m.id WHERE d.transaksi_id = '".$trx['id']."'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Struk #<?= substr($uuid,0,8) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eee; }
        .struk-paper { background: white; width: 350px; margin: 20px auto; padding: 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); font-family: 'Courier New', Courier, monospace; }
        .dashed-line { border-top: 2px dashed #ddd; margin: 10px 0; }
        @media print {
            body * { visibility: hidden; }
            .struk-paper, .struk-paper * { visibility: visible; }
            .struk-paper { position: absolute; left: 0; top: 0; box-shadow: none; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="struk-paper">
    <div class="text-center mb-3">
        <h5 class="fw-bold mb-0">MODERN BITES</h5>
        <small><?= $trx['nama_cabang'] ?></small><br>
        <small style="font-size:10px;"><?= $trx['alamat'] ?></small>
    </div>
    
    <div class="dashed-line"></div>
    
    <div class="d-flex justify-content-between small">
        <span>No: #<?= substr($uuid,0,8) ?></span>
        <span><?= date('d/m/y H:i', strtotime($trx['created_at'])) ?></span>
    </div>
    <div class="small">Meja: <?= $trx['nomor_meja'] ?> | <?= $trx['nama_pelanggan'] ?></div>
    
    <div class="dashed-line"></div>
    
    <?php while($item = $details->fetch_assoc()): ?>
    <div class="d-flex justify-content-between small mb-1">
        <span><?= $item['qty'] ?>x <?= $item['nama_menu'] ?></span>
        <span><?= number_format($item['subtotal']) ?></span>
    </div>
    <?php endwhile; ?>
    
    <div class="dashed-line"></div>
    
    <div class="d-flex justify-content-between fw-bold">
        <span>TOTAL</span>
        <span>Rp <?= number_format($trx['total_harga']) ?></span>
    </div>
    
    <div class="d-flex justify-content-between small">
        <span>Bayar</span>
        <span><?= number_format($trx['uang_bayar']) ?></span>
    </div>
    <div class="d-flex justify-content-between small">
        <span>Kembali</span>
        <span><?= number_format($trx['kembalian']) ?></span>
    </div>
    
    <div class="dashed-line"></div>
    
    <div class="text-center small mt-3">
        <div class="fw-bold mb-2">LUNAS</div>
        Terima Kasih atas Kunjungan Anda!
    </div>

    <div class="mt-4 d-grid gap-2 no-print">
        <button onclick="window.print()" class="btn btn-dark btn-sm">Cetak Struk</button>
        <a href="index.php" class="btn btn-link btn-sm text-decoration-none">Kembali ke Menu</a>
    </div>
</div>

<?php if(isset($_GET['print'])): ?>
<script>window.onload = function() { window.print(); }</script>
<?php endif; ?>

</body>
</html>