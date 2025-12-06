<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Riwayat Transaksi";
$active_menu = "riwayat";

// --- 1. LOGIKA FILTER CABANG ---
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';

$view_cabang = "";
$is_global = false;

if ($level == 'admin') {
    $view_cabang = $_SESSION['view_cabang_id'] ?? 'pusat';
    if ($view_cabang == 'pusat') {
        $is_global = true;
    } else {
        $cabang_id = $view_cabang;
    }
}

// --- 2. LOGIKA PAGINATION ---
$limit = 20; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// --- 3. BUILD QUERY ---
// Base Query (Filter)
$where_sql = "";
if (!$is_global) {
    $target_cabang = ($level == 'admin') ? $view_cabang : $_SESSION['cabang_id'];
    $where_sql = " WHERE m.cabang_id = '$target_cabang'";
}

// A. Hitung Total Data (Untuk Navigasi Halaman)
$sql_count = "SELECT COUNT(*) as total 
              FROM transaksi t
              JOIN meja m ON t.meja_id = m.id
              $where_sql";
$total_data = $koneksi->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// B. Ambil Data (Dengan Limit)
$sql = "SELECT t.*, m.nomor_meja, u.nama as nama_kasir, c.nama_cabang 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        LEFT JOIN cabang c ON m.cabang_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_sql
        ORDER BY t.created_at DESC 
        LIMIT $start, $limit"; // Pagination Query

$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            Daftar Transaksi
            <?php if($is_global): ?>
                <span class="badge bg-secondary ms-2">Semua Cabang</span>
            <?php endif; ?>
        </h6>
        <div>
            <small class="text-muted me-3">Total: <?= number_format($total_data) ?> Transaksi</small>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No</th> 
                        <th>Waktu</th>
                        <?php if($is_global): ?> <th>Cabang</th> <?php endif; ?>
                        <th>Meja</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($data->num_rows > 0): ?>
                        <?php 
                        $no = $start + 1; // Penomoran lanjut antar halaman
                        while($row = $data->fetch_assoc()): 
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted"><?= $no++ ?></td>
                            
                            <td>
                                <div class="fw-bold"><?= date('d/m/Y', strtotime($row['created_at'])) ?></div>
                                <small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                            </td>

                            <?php if($is_global): ?>
                                <td><span class="badge bg-info text-dark"><i class="fas fa-store me-1"></i> <?= $row['nama_cabang'] ?></span></td>
                            <?php endif; ?>

                            <td><span class="badge bg-dark">Meja <?= $row['nomor_meja'] ?></span></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td class="fw-bold text-primary">Rp <?= number_format($row['total_harga']) ?></td>
                            <td>
                                <?php 
                                    $st = $row['status_pembayaran'];
                                    if ($st == 'settlement') {
                                        echo "<span class='badge bg-success'>LUNAS</span>";
                                    } elseif ($st == 'pending') {
                                        echo "<span class='badge bg-warning text-dark'>PENDING</span>";
                                    } elseif ($st == 'cancel' || $st == 'expire') {
                                        echo "<span class='badge bg-secondary'>BATAL</span>";
                                    } else {
                                        echo "<span class='badge bg-danger'>".strtoupper($st)."</span>";
                                    }
                                ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick="showDetail('<?= $row['uuid'] ?>')" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <?php if($row['status_pembayaran'] == 'settlement'): ?>
                                    <a href="../penjualan/struk.php?uuid=<?= $row['uuid'] ?>&print=true" target="_blank" class="btn btn-sm btn-secondary ms-1" title="Cetak Struk">
                                        <i class="fas fa-print"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $is_global ? 8 : 7 ?>" class="text-center py-5 text-muted">
                                <img src="../assets/images/pngkey.com-food-network-logo-png-430444.png" width="50" class="mb-3 opacity-25"><br>
                                Belum ada data transaksi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white py-3">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
                </li>

                <?php
                // Tampilkan max 5 halaman di sekitar halaman aktif
                $start_loop = max(1, $page - 2);
                $end_loop = min($total_pages, $page + 2);

                if($start_loop > 1) { 
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if($start_loop > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for ($i = $start_loop; $i <= $end_loop; $i++): 
                ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php 
                // [FIXED] Tag PHP ditambahkan disini agar logika IF tidak bocor jadi teks HTML
                if($end_loop < $total_pages) {
                    if($end_loop < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'">'.$total_pages.'</a></li>';
                }
                ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                    <span>ID: <strong id="d_id"></strong></span>
                    <span id="d_waktu" class="text-muted"></span>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Pelanggan</div>
                    <div class="fw-bold" id="d_pelanggan"></div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-borderless">
                        <thead class="text-muted border-bottom">
                            <tr><th>Menu</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody id="d_items"></tbody>
                    </table>
                </div>
                <div class="bg-light p-3 rounded">
                    <div class="d-flex justify-content-between fw-bold mb-2">
                        <span>TOTAL TAGIHAN</span>
                        <span id="d_total" class="text-primary fs-5"></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Bayar</span>
                        <span id="d_bayar"></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Kembalian</span>
                        <span id="d_kembalian"></span>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    Status: <span id="d_status_pesanan" class="badge"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(uuid) {
    Swal.fire({title: 'Memuat...', didOpen: () => Swal.showLoading()});

    fetch(`api/get_detail_transaksi.php?uuid=${uuid}`)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.status === 'success') {
            const h = data.header;
            document.getElementById('d_id').innerText = '#' + h.id;
            document.getElementById('d_waktu').innerText = h.created_at;
            document.getElementById('d_pelanggan').innerText = h.nama_pelanggan + ' (Meja ' + h.nomor_meja + ')';
            document.getElementById('d_total').innerText = 'Rp ' + parseInt(h.total_harga).toLocaleString('id-ID');
            document.getElementById('d_bayar').innerText = 'Rp ' + parseInt(h.uang_bayar).toLocaleString('id-ID');
            document.getElementById('d_kembalian').innerText = 'Rp ' + parseInt(h.kembalian).toLocaleString('id-ID');
            
            const badge = document.getElementById('d_status_pesanan');
            badge.innerText = h.status_pembayaran.toUpperCase();
            badge.className = 'badge ' + (h.status_pembayaran === 'settlement' ? 'bg-success' : 'bg-warning');

            let htmlItems = '';
            data.items.forEach(item => {
                htmlItems += `<tr><td>${item.nama_menu}</td><td class="text-end">${item.qty}x</td><td class="text-end">Rp ${parseInt(item.subtotal).toLocaleString()}</td></tr>`;
            });
            document.getElementById('d_items').innerHTML = htmlItems;

            new bootstrap.Modal(document.getElementById('detailModal')).show();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => Swal.fire('Error', 'Gagal mengambil data', 'error'));
}
</script>

<?php include '../layouts/admin/footer.php'; ?>