<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Laporan Penjualan";
$active_menu = "laporan";

// --- 1. LOGIKA FILTER ---
$level = $_SESSION['level'];
$view_cabang = 'pusat';

if ($level == 'admin') {
    $view_cabang = $_SESSION['view_cabang_id'] ?? 'pusat';
} else {
    $view_cabang = $_SESSION['cabang_id'];
}
$is_global = ($view_cabang == 'pusat');

// Filter Tanggal
$start_date = $_GET['start'] ?? date('Y-m-01'); 
$end_date   = $_GET['end'] ?? date('Y-m-d');

// --- 2. BASE QUERY ---
$where = "WHERE t.status_pembayaran = 'settlement' 
          AND DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";

if (!$is_global) {
    $where .= " AND t.meja_id IN (SELECT id FROM meja WHERE cabang_id = '$view_cabang')";
}

// --- 3. QUERY UTAMA (RINGKASAN) ---
$sql_summary = "SELECT SUM(t.total_harga) as omset, COUNT(*) as trans 
                FROM transaksi t $where";
$summary = $koneksi->query($sql_summary)->fetch_assoc();
$omset = $summary['omset'] ?? 0;
$total_trx = $summary['trans'] ?? 0;

// --- 4. QUERY GRAFIK HARIAN ---
$sql_chart = "SELECT DATE(t.created_at) as tgl, SUM(t.total_harga) as total 
              FROM transaksi t $where 
              GROUP BY DATE(t.created_at) 
              ORDER BY tgl ASC";
$chart_res = $koneksi->query($sql_chart);

$labels = [];
$data_chart = [];
while($c = $chart_res->fetch_assoc()) {
    $labels[] = date('d M', strtotime($c['tgl']));
    $data_chart[] = $c['total'];
}

// --- 5. QUERY TOP MENU ---
$sql_top = "SELECT m.nama_menu, SUM(d.qty) as terjual 
            FROM transaksi_detail d
            JOIN transaksi t ON d.transaksi_id = t.id
            JOIN menu m ON d.menu_id = m.id
            $where
            GROUP BY d.menu_id
            ORDER BY terjual DESC LIMIT 5";
$top_menus = $koneksi->query($sql_top);

// --- 6. [BARU] QUERY PERFORMA PER CABANG (HANYA JIKA GLOBAL) ---
$cabang_labels = [];
$cabang_data = [];
$cabang_colors = []; // Warna-warni otomatis
$list_performa = [];

if ($is_global) {
    $sql_cabang = "SELECT c.nama_cabang, COUNT(t.id) as total_trx, SUM(t.total_harga) as total_omset
                   FROM transaksi t
                   JOIN meja m ON t.meja_id = m.id
                   JOIN cabang c ON m.cabang_id = c.id
                   WHERE t.status_pembayaran = 'settlement' 
                   AND DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'
                   GROUP BY c.id
                   ORDER BY total_omset DESC";
    $res_cabang = $koneksi->query($sql_cabang);
    
    // Warna-warni untuk chart (Palette Cerah)
    $palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];
    $i = 0;

    while($row = $res_cabang->fetch_assoc()) {
        $cabang_labels[] = $row['nama_cabang'];
        $cabang_data[] = $row['total_omset'];
        $cabang_colors[] = $palette[$i % count($palette)]; // Loop warna
        $list_performa[] = $row;
        $i++;
    }
}

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row align-items-end g-2">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="start" class="form-control form-control-sm" value="<?= $start_date ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="end" class="form-control form-control-sm" value="<?= $end_date ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="laporan_pdf.php?start=<?= $start_date ?>&end=<?= $end_date ?>" target="_blank" class="btn btn-danger btn-sm w-100 fw-bold">
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card bg-primary text-white border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Total Omset (<?= $is_global ? 'Semua Cabang' : 'Cabang Terpilih' ?>)</h6>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($omset, 0, ',', '.') ?></h2>
                    </div>
                    <i class="fas fa-wallet fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Total Transaksi Selesai</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($total_trx) ?> <span class="fs-6 fw-normal">Pesanan</span></h2>
                    </div>
                    <i class="fas fa-receipt fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($is_global && !empty($list_performa)): ?>
<div class="row mb-4">
    <div class="col-lg-5 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-pie me-2"></i>Kontribusi Pendapatan Cabang</h6>
            </div>
            <div class="card-body">
                <div style="height: 250px;">
                    <canvas id="branchChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list-ol me-2"></i>Rincian Performa Cabang</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Nama Cabang</th>
                                <th class="text-center">Jumlah Trx</th>
                                <th class="text-end pe-4">Total Pendapatan</th>
                                <th class="text-end pe-4">% Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($list_performa as $p): 
                                $persen = ($omset > 0) ? ($p['total_omset'] / $omset) * 100 : 0;
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= $p['nama_cabang'] ?></td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $p['total_trx'] ?></span></td>
                                <td class="text-end pe-4 fw-bold text-success">Rp <?= number_format($p['total_omset'], 0, ',', '.') ?></td>
                                <td class="text-end pe-4 small text-muted">
                                    <?= number_format($persen, 1) ?>%
                                    <div class="progress" style="height: 3px; width: 50px; float: right; margin-top:5px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $persen ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-area me-2"></i>Trend Pendapatan Harian</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-crown me-2"></i>5 Menu Terlaris</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if($top_menus->num_rows > 0): ?>
                        <?php $rank = 1; while($tm = $top_menus->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 border-bottom-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold text-secondary" style="width:28px;height:28px;">
                                    <?= $rank++ ?>
                                </div>
                                <span class="fw-bold text-dark"><?= $tm['nama_menu'] ?></span>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= $tm['terjual'] ?> Terjual</span>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center py-5 text-muted">Belum ada data penjualan.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. CHART HARIAN (LINE)
    const ctxSales = document.getElementById('salesChart');
    if(ctxSales) {
        new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?= json_encode($data_chart) ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. CHART CABANG (DOUGHNUT) - HANYA JIKA MODE GLOBAL
    <?php if($is_global && !empty($cabang_labels)): ?>
    const ctxBranch = document.getElementById('branchChart');
    if(ctxBranch) {
        new Chart(ctxBranch, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($cabang_labels) ?>,
                datasets: [{
                    data: <?= json_encode($cabang_data) ?>,
                    backgroundColor: <?= json_encode($cabang_colors) ?>,
                    hoverOffset: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = Math.round((value / total) * 100) + '%';
                                return label + ': Rp ' + value.toLocaleString('id-ID') + ' (' + percentage + ')';
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
    <?php endif; ?>
});
</script>

<?php include '../layouts/admin/footer.php'; ?>