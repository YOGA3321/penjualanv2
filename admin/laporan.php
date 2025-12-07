<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Laporan Penjualan";
$active_menu = "laporan";

// --- FILTER ---
$level = $_SESSION['level'];
$view_cabang = ($level == 'admin') ? ($_SESSION['view_cabang_id'] ?? 'pusat') : $_SESSION['cabang_id'];
$is_global = ($view_cabang == 'pusat');

$start_date = $_GET['start'] ?? date('Y-m-01'); 
$end_date   = $_GET['end'] ?? date('Y-m-d');

// --- BASE QUERY ---
$where = "WHERE t.status_pembayaran = 'settlement' AND DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
if (!$is_global) {
    $where .= " AND t.meja_id IN (SELECT id FROM meja WHERE cabang_id = '$view_cabang')";
}

// --- 1. RINGKASAN (TOTAL SEMUA, TANPA PAGINATION) ---
$sql_summary = "SELECT 
                    SUM(t.total_harga) as omset_bersih, 
                    SUM(t.diskon) as total_diskon,
                    COUNT(*) as total_trx 
                FROM transaksi t $where";
$summary = $koneksi->query($sql_summary)->fetch_assoc();

// --- 2. LIST DATA (DENGAN PAGINATION) ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Hitung Total Rows untuk Pagination
$sql_count = "SELECT COUNT(*) as total FROM transaksi t $where";
$total_rows = $koneksi->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Query Data Per Halaman
$sql_list = "SELECT t.*, m.nomor_meja, c.nama_cabang 
             FROM transaksi t 
             JOIN meja m ON t.meja_id = m.id 
             JOIN cabang c ON m.cabang_id = c.id 
             $where 
             ORDER BY t.created_at DESC 
             LIMIT $start, $limit";
$list_trx = $koneksi->query($sql_list);

include '../layouts/admin/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body">
                <h6 class="opacity-75">Total Omset (Bersih)</h6>
                <h3 class="fw-bold mb-0">Rp <?= number_format($summary['omset_bersih'],0,',','.') ?></h3>
                <small><?= $summary['total_trx'] ?> Transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger text-white h-100">
            <div class="card-body">
                <h6 class="opacity-75">Total Diskon Diberikan</h6>
                <h3 class="fw-bold mb-0">Rp <?= number_format($summary['total_diskon'],0,',','.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="small text-muted">Dari</label>
                            <input type="date" name="start" id="startDate" class="form-control form-control-sm" value="<?= $start_date ?>">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted">Sampai</label>
                            <input type="date" name="end" id="endDate" class="form-control form-control-sm" value="<?= $end_date ?>">
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-filter"></i> Filter</button>
                            <a href="laporan_pdf.php?start=<?= $start_date ?>&end=<?= $end_date ?>" target="_blank" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Rincian Transaksi</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Cabang</th>
                    <th>Metode</th>
                    <th>Total Asli</th>
                    <th class="text-danger">Diskon</th>
                    <th class="fw-bold">Total Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if($list_trx->num_rows > 0): ?>
                    <?php while($row = $list_trx->fetch_assoc()): 
                        $asli = $row['total_harga'] + $row['diskon'];
                    ?>
                    <tr>
                        <td class="ps-4 text-muted small"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                            <small class="text-muted">Meja <?= $row['nomor_meja'] ?></small>
                        </td>
                        <td><?= $row['nama_cabang'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= strtoupper($row['metode_pembayaran']) ?></span></td>
                        <td class="text-muted">Rp <?= number_format($asli) ?></td>
                        <td class="text-danger">
                            <?php if($row['diskon']>0): ?>
                                - Rp <?= number_format($row['diskon']) ?>
                                <br><small class="text-muted" style="font-size:0.65rem"><?= $row['kode_voucher'] ?></small>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td class="fw-bold text-primary">Rp <?= number_format($row['total_harga']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data di periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?start=<?= $start_date ?>&end=<?= $end_date ?>&page=<?= $page-1 ?>">Prev</a>
                </li>
                
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <?php if($i == 1 || $i == $total_pages || ($i >= $page-1 && $i <= $page+1)): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?start=<?= $start_date ?>&end=<?= $end_date ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php elseif($i == $page-2 || $i == $page+2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?start=<?= $start_date ?>&end=<?= $end_date ?>&page=<?= $page+1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <div class="text-center text-muted small mt-2">
            Halaman <?= $page ?> dari <?= $total_pages ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Tidak pakai debounce karena date picker butuh klik, bukan ketik
    // User biasanya klik filter manual atau kita bisa auto submit saat change
    document.getElementById('startDate').addEventListener('change', function() {
        // Opsional: document.getElementById('filterForm').submit(); 
        // Lebih baik manual klik tombol filter agar tidak refresh 2x saat pilih range
    });
</script>

<?php include '../layouts/admin/footer.php'; ?>