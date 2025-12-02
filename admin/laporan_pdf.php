<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }

require_once '../auth/koneksi.php';
require_once '../vendor/autoload.php'; // Load DomPDF

use Dompdf\Dompdf;
use Dompdf\Options;

// --- 1. LOGIKA FILTER CABANG (DIPERBAIKI) ---
$level = $_SESSION['level'] ?? '';
$view_cabang = 'pusat';

if ($level == 'admin') {
    // Ambil dari Session View (yang diset di header)
    $view_cabang = $_SESSION['view_cabang_id'] ?? 'pusat';
} else {
    // Karyawan terkunci di cabangnya
    $view_cabang = $_SESSION['cabang_id'];
}
$is_global = ($view_cabang == 'pusat');

// Ambil Label Nama Cabang untuk Judul
$label_cabang = "Semua Cabang";
if (!$is_global) {
    $q_cab = $koneksi->query("SELECT nama_cabang FROM cabang WHERE id = '$view_cabang'");
    if ($row_c = $q_cab->fetch_assoc()) {
        $label_cabang = "Cabang " . $row_c['nama_cabang'];
    }
}

// Filter Tanggal
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date   = $_GET['end'] ?? date('Y-m-d');

// --- 2. QUERY DATA ---
$where = "WHERE t.status_pembayaran = 'settlement' 
          AND DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";

// [FIX] Filter Cabang yang Benar
if (!$is_global) {
    $where .= " AND t.meja_id IN (SELECT id FROM meja WHERE cabang_id = '$view_cabang')";
}

// [FIX] Sorting ASC (Lama di Atas, Baru di Bawah)
$sql = "SELECT t.*, m.nomor_meja, u.nama as kasir, c.nama_cabang 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        LEFT JOIN cabang c ON m.cabang_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        $where 
        ORDER BY t.created_at ASC"; // <-- ASC agar urut tanggal

$result = $koneksi->query($sql);
$total_omset = 0;

// --- 3. BUAT HTML PDF ---
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .header h4 { margin: 5px 0; font-weight: normal; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 8px; font-size: 9pt; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 8pt; background: #eee; border: 1px solid #ccc; }
        
        .footer { margin-top: 30px; text-align: right; font-size: 8pt; color: #666; font-style: italic; }
        .total-row { background-color: #e6e6e6; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Penjualan</h2>
        <h4>Modern Bites - <?= $label_cabang ?></h4>
        <small>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Waktu</th>
                <?php if($is_global): ?><th width="15%">Cabang</th><?php endif; ?>
                <th width="20%">Pelanggan</th>
                <th width="10%">Meja</th>
                <th width="15%">Metode</th>
                <th width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if($result->num_rows > 0):
                while($row = $result->fetch_assoc()): 
                    $total_omset += $row['total_harga'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td>
                    <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                    <small><?= date('H:i', strtotime($row['created_at'])) ?></small>
                </td>
                
                <?php if($is_global): ?>
                    <td><?= $row['nama_cabang'] ?></td>
                <?php endif; ?>
                
                <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                <td class="text-center"><?= $row['nomor_meja'] ?></td>
                <td class="text-center" style="text-transform:uppercase;">
                    <?= $row['metode_pembayaran'] ?>
                </td>
                <td class="text-right">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="<?= $is_global ? 7 : 6 ?>" class="text-center" style="padding: 20px;">Tidak ada data transaksi pada periode ini.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="<?= $is_global ? 6 : 5 ?>" class="text-right">TOTAL PENDAPATAN</td>
                <td class="text-right">Rp <?= number_format($total_omset, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak otomatis oleh sistem pada: <?= date('d F Y H:i:s') ?> <br>
        User: <?= $_SESSION['nama'] ?? 'Administrator' ?>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// 4. RENDER PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nama File Download
$nama_file_cabang = $is_global ? "Semua_Cabang" : str_replace(" ", "_", $label_cabang);
$filename = "Laporan_" . $nama_file_cabang . "_" . date('Ymd') . ".pdf";

$dompdf->stream($filename, array("Attachment" => true));
?>