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

// Buat HTML Struk (Thermal 58mm Friendly)
ob_start();
?>
<html>
<head>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 9pt; margin: 0; padding: 0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .bold { font-weight: bold; }
        table { width: 100%; }
        td { vertical-align: top; }
    </style>
</head>
<body>
    <div class="center">
        <div class="bold" style="font-size:11pt">WAROENG MODERN BITES</div>
        <?= $trx['nama_cabang'] ?><br>
        <?= $trx['alamat'] ?>
    </div>
    <div class="line"></div>
    <div>
        Tgl: <?= date('d/m/y H:i', strtotime($trx['created_at'])) ?><br>
        Order: #<?= substr($uuid, 0, 8) ?><br>
        Meja: <?= $trx['nomor_meja'] ?> | <?= $trx['nama_pelanggan'] ?>
    </div>
    <div class="line"></div>
    <table>
        <?php 
        $subtotal_murni = 0;
        while($item = $details->fetch_assoc()): 
            $subtotal_murni += $item['subtotal'];
        ?>
        <tr>
            <td colspan="2"><?= $item['nama_menu'] ?></td>
        </tr>
        <tr>
            <td><?= $item['qty'] ?> x <?= number_format($item['harga_satuan']) ?></td>
            <td class="right"><?= number_format($item['subtotal']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <div class="line"></div>
    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right"><?= number_format($subtotal_murni) ?></td>
        </tr>
        <?php if($trx['diskon'] > 0): ?>
        <tr>
            <td>Diskon <?= $trx['kode_voucher'] ? '('.$trx['kode_voucher'].')' : '' ?></td>
            <td class="right">-<?= number_format($trx['diskon']) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="bold" style="font-size:10pt">
            <td>TOTAL</td>
            <td class="right">Rp <?= number_format($trx['total_harga']) ?></td>
        </tr>
        
        <tr><td colspan="2" style="height:5px"></td></tr>
        
        <tr>
            <td>Bayar (<?= strtoupper($trx['metode_pembayaran']) ?>)</td>
            <td class="right"><?= number_format($trx['uang_bayar']) ?></td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="right"><?= number_format($trx['kembalian']) ?></td>
        </tr>
        
        <?php if(!empty($trx['user_id']) && $trx['poin_didapat'] > 0): ?>
        <tr>
            <td colspan="2" class="center" style="padding-top:5px; font-size:8pt;">
                *** Anda Dapat <?= $trx['poin_didapat'] ?> Poin ***
            </td>
        </tr>
        <?php endif; ?>
    </table>
    <div class="line"></div>
    <div class="center">
        -- TERIMA KASIH --<br>
        Simpan struk ini sebagai bukti pembayaran.
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 164, 600], 'portrait'); 
$dompdf->render();
$dompdf->stream("struk-$uuid.pdf", array("Attachment" => false));
?>