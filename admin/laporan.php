<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Laporan Penjualan";
$active_menu = "laporan";

// --- 1. LOGIKA FILTER CABANG ---
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
// Jika Admin Pusat sedang mode "Lihat Cabang", gunakan ID cabang tersebut
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}

// --- 2. LOGIKA FILTER TANGGAL ---
// Default: Dari tanggal 1 bulan ini sampai hari ini
$start_date = $_GET['start'] ?? date('Y-m-01'); 
$end_date   = $_GET['end'] ?? date('Y-m-d');

// --- 3. QUERY DASAR (WHERE) ---
// Hanya ambil yang LUNAS (settlement) dan dalam rentang tanggal
$where = "WHERE t.status_pembayaran = 'settlement' 
          AND DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";

// Filter Cabang (Admin Pusat lihat semua jika mode Global, Cabang lihat data sendiri)
if ($level != 'admin' || isset($_SESSION['view_cabang_id'])) {
    // Join ke tabel meja untuk filter cabang
    $where .= " AND t.meja_id IN (SELECT id FROM meja WHERE cabang_id = '$cabang_id')";
}

// --- 4. EKSEKUSI QUERY ---

// A. Ringkasan Total
$sql_summary = "SELECT SUM(t.total_harga) as omset, COUNT(*) as trans 
                FROM transaksi t $where";
$summary = $koneksi->query($sql_summary)->fetch_assoc();
$omset = $summary['omset'] ?? 0;
$total_trx = $summary['trans'] ?? 0;

// B. Data Grafik Harian
$sql_chart = "SELECT DATE(t.created_at) as tgl, SUM(t.total_harga) as total 
              FROM transaksi t $where 
              GROUP BY DATE(t.created_at) 
              ORDER BY tgl ASC";
$chart_res = $koneksi->query($sql_chart);

$labels = [];
$data_chart = [];
while($c = $chart_res->fetch_assoc()) {
    $labels[] = date('d M', strtotime($c['tgl'])); // Format: 01 Jan
    $data_chart[] = $c['total'];
}

// C. Top 5 Menu Terlaris
$sql_top = "SELECT m.nama_menu, SUM(d.qty) as terjual 
            FROM transaksi_detail d
            JOIN transaksi t ON d.transaksi_id = t.id
            JOIN menu m ON d.menu_id = m.id
            $where
            GROUP BY d.menu_id
            ORDER BY terjual DESC LIMIT 5";
$top_menus = $koneksi->query($sql_top);

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                <input type="date" name="start" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                <input type="date" name="end" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-filter me-2"></i> Tampilkan Data
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card bg-primary text-white border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small ls-1 mb-1">Total Omset</h6>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($omset, 0, ',', '.') ?></h2>
                    </div>
                    <i class="fas fa-wallet fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small ls-1 mb-1">Total Transaksi</h6>
                        <h2 class="fw-bold mb-0"><?= number_format($total_trx) ?> <span class="fs-6 fw-normal">Pesanan</span></h2>
                    </div>
                    <i class="fas fa-receipt fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-line me-2"></i>Grafik Pendapatan Harian</h6>
            </div>
            <div class="card-body">
                <canvas id="salesChart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-crown me-2"></i>Menu Terlaris</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if($top_menus->num_rows > 0): ?>
                        <?php $rank = 1; while($tm = $top_menus->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-light text-dark border me-3 rounded-circle" style="width:25px;height:25px;display:flex;align-items:center;justify-content:center;"><?= $rank++ ?></span>
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
    const ctx = document.getElementById('salesChart');
    
    // Data dari PHP
    const labels = <?= json_encode($labels) ?>;
    const data = <?= json_encode($data_chart) ?>;

    if(labels.length === 0) {
        // Tampilkan pesan kosong jika tidak ada data
        ctx.parentNode.innerHTML = '<div class="text-center py-5 text-muted">Tidak ada data untuk periode ini.</div>';
        return;
    }

    new Chart(ctx, {
        type: 'bar', // Tipe Bar Chart lebih cocok untuk harian
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: data,
                backgroundColor: '#0d6efd',
                borderRadius: 5,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 2] },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value/1000) + 'k';
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

<?php include '../layouts/admin/footer.php'; ?>