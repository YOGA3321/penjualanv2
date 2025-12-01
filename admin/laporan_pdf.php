<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }

require_once '../auth/koneksi.php';
require_once '../vendor/autoload.php'; // Load DomPDF

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. AMBIL DATA (Sama seperti di laporan.php)
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date   = $_GET['end'] ?? date('Y-m-d');

// Judul Laporan
$cabang_label = ($level == 'admin' && (!isset($_SESSION['view_cabang_id']) || $_SESSION['view_cabang_id'] == 'pusat')) ? 'Semua Cabang' : 'Cabang Terpilih';

// Query Data Transaksi
$where = "WHERE t.status_pembayaran = 'settlement' AND DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
if ($level != 'admin' || (isset($_SESSION['view_cabang_id']))) {
    $where .= " AND t.meja_id IN (SELECT id FROM meja WHERE cabang_id = '$cabang_id')";
}

$sql = "SELECT t.*, m.nomor_meja, u.nama as kasir 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        LEFT JOIN users u ON t.user_id = u.id
        $where 
        ORDER BY t.created_at DESC";
$result = $koneksi->query($sql);

$total_omset = 0;

// 2. BUAT HTML UNTUK PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h4 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background-color: #eee; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: right; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Penjualan - Modern Bites</h2>
        <h4>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?></h4>
        <small><?= $cabang_label ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl & Waktu</th>
                <th>Pelanggan</th>
                <th>Meja</th>
                <th>Metode</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($row = $result->fetch_assoc()): 
                $total_omset += $row['total_harga'];
            ?>
            <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                <td style="text-align:center;"><?= $row['nomor_meja'] ?></td>
                <td style="text-align:center; text-transform:uppercase;"><?= $row['metode_pembayaran'] ?></td>
                <td class="text-right">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            
            <?php if($result->num_rows == 0): ?>
            <tr><td colspan="6" style="text-align:center;">Tidak ada data pada periode ini.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL PENDAPATAN</th>
                <th class="text-right">Rp <?= number_format($total_omset, 0, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?><br>Oleh: <?= $_SESSION['nama'] ?? 'Admin' ?></p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// 3. GENERATE PDF
$options = new Options();
$options->set('isRemoteEnabled', true); // Agar bisa load gambar jika ada
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Download File
$filename = "Laporan_Penjualan_" . date('Ymd') . ".pdf";
$dompdf->stream($filename, array("Attachment" => true));
?>