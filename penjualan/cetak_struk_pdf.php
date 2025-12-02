<?php
session_start();
require_once '../auth/koneksi.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$uuid = $_GET['uuid'] ?? '';
if(empty($uuid)) die("UUID invalid");

// Ambil Data Transaksi
$query = "SELECT t.*, m.nomor_meja, c.nama_cabang, c.alamat 
          FROM transaksi t 
          JOIN meja m ON t.meja_id = m.id
          JOIN cabang c ON m.cabang_id = c.id
          WHERE t.uuid = '$uuid'";
$trx = $koneksi->query($query)->fetch_assoc();

if(!$trx) die("Data tidak ditemukan");

// Ambil Detail
$details = $koneksi->query("SELECT d.*, m.nama_menu FROM transaksi_detail d JOIN menu m ON d.menu_id = m.id WHERE d.transaksi_id = '".$trx['id']."'");

// Buat HTML Struk
ob_start();
?>
<html>
<head>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 10pt; }
        .center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 10px 0; }
        .flex { display: flex; justify-content: space-between; }
        table { width: 100%; }
        td.right { text-align: right; }
    </style>
</head>
<body>
    <div class="center">
        <h3 style="margin:0;"><?= $trx['nama_cabang'] ?></h3>
        <small><?= $trx['alamat'] ?></small>
    </div>
    <div class="line"></div>
    <div>
        No: #<?= substr($uuid,0,8) ?><br>
        Tgl: <?= date('d/m/y H:i', strtotime($trx['created_at'])) ?><br>
        Meja: <?= $trx['nomor_meja'] ?> | <?= $trx['nama_pelanggan'] ?>
    </div>
    <div class="line"></div>
    <table>
        <?php while($item = $details->fetch_assoc()): ?>
        <tr>
            <td><?= $item['qty'] ?>x <?= $item['nama_menu'] ?></td>
            <td class="right"><?= number_format($item['subtotal']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <div class="line"></div>
    <table>
        <tr>
            <td><b>TOTAL</b></td>
            <td class="right"><b>Rp <?= number_format($trx['total_harga']) ?></b></td>
        </tr>
        <tr>
            <td>Bayar (<?= strtoupper($trx['metode_pembayaran']) ?>)</td>
            <td class="right"><?= number_format($trx['uang_bayar']) ?></td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="right"><?= number_format($trx['kembalian']) ?></td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="center">
        LUNAS<br>
        Terima Kasih atas kunjungan Anda!
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->setPaper([0, 0, 226.77, 600], 'portrait'); // Ukuran Kertas Struk (80mm)
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("Struk_#".substr($uuid,0,8).".pdf", array("Attachment" => true));
?>