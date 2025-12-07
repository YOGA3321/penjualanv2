<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Riwayat Transaksi";
$active_menu = "riwayat";

// --- 1. FILTER CABANG ---
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
$is_global = ($level == 'admin' && ($_SESSION['view_cabang_id'] ?? 'pusat') == 'pusat');

// --- 2. BASE QUERY (WHERE) ---
$where_sql = " WHERE 1=1 ";
if (!$is_global) {
    $target_cabang = ($level == 'admin') ? $_SESSION['view_cabang_id'] : $_SESSION['cabang_id'];
    $where_sql .= " AND m.cabang_id = '$target_cabang'";
}

// Search Logic
$search_kw = "";
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search_kw = $koneksi->real_escape_string($_GET['search']);
    $where_sql .= " AND (t.nama_pelanggan LIKE '%$search_kw%' OR t.uuid LIKE '%$search_kw%')";
}

// --- 3. PAGINATION LOGIC ---
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Hitung Total Data (Untuk Pagination)
$sql_count = "SELECT COUNT(*) as total 
              FROM transaksi t
              JOIN meja m ON t.meja_id = m.id
              JOIN cabang c ON m.cabang_id = c.id
              $where_sql";
$total_data = $koneksi->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// --- 4. DATA QUERY ---
$sql = "SELECT t.*, m.nomor_meja, c.nama_cabang 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        JOIN cabang c ON m.cabang_id = c.id
        $where_sql
        ORDER BY t.created_at DESC 
        LIMIT $start, $limit";
$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Riwayat Transaksi</h5>
        
        <form class="d-flex" method="GET" id="searchForm">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" id="searchInput" class="form-control border-start-0" 
                       placeholder="Cari nama/struk..." value="<?= htmlspecialchars($search_kw) ?>" autocomplete="off">
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Waktu</th>
                    <th>Pelanggan</th>
                    <th>Cabang</th>
                    <th>Total</th>
                    <th>Diskon</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if($data->num_rows > 0): ?>
                    <?php while($row = $data->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4 text-muted small"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                            <small class="text-muted text-uppercase">#<?= substr($row['uuid'],0,8) ?></small>
                        </td>
                        <td><?= $row['nama_cabang'] ?> <small>(Meja <?= $row['nomor_meja'] ?>)</small></td>
                        <td class="fw-bold text-primary">Rp <?= number_format($row['total_harga']) ?></td>
                        <td class="text-danger">
                            <?php if($row['diskon'] > 0): ?>
                                -<?= number_format($row['diskon']) ?>
                                <br><small class="text-muted"><?= $row['kode_voucher'] ?></small>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td><?= strtoupper($row['metode_pembayaran']) ?></td>
                        <td>
                            <?php 
                                $s = $row['status_pembayaran'];
                                $cls = ($s=='settlement')?'success':(($s=='pending')?'warning':'danger');
                                echo "<span class='badge bg-$cls'>".strtoupper($s)."</span>";
                            ?>
                        </td>
                        <td class="text-end pe-4" style="min-width: 100px;">
                            <button class="btn btn-sm btn-info text-white me-1" onclick="showDetail(<?= $row['id'] ?>)" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="../penjualan/cetak_struk_pdf.php?uuid=<?= $row['uuid'] ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">Tidak ada data ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= $search_kw ?>">Prev</a>
                </li>
                
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <?php if($i == 1 || $i == $total_pages || ($i >= $page-1 && $i <= $page+1)): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= $search_kw ?>"><?= $i ?></a>
                        </li>
                    <?php elseif($i == $page-2 || $i == $page+2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= $search_kw ?>">Next</a>
                </li>
            </ul>
        </nav>
        <div class="text-center text-muted small mt-2">
            Halaman <?= $page ?> dari <?= $total_pages ?> (Total <?= $total_data ?> Data)
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    let timeout = null;
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');

    searchInput.addEventListener('input', function() {
        // Reset timer jika user masih mengetik
        clearTimeout(timeout);

        // Tunggu 600ms (0.6 detik) setelah berhenti mengetik
        timeout = setTimeout(() => {
            searchForm.submit();
        }, 600);
    });
    
    // Focus kembali ke input setelah reload (opsional, browser modern biasanya ingat)
    searchInput.focus();
    var val = searchInput.value; 
    searchInput.value = ''; 
    searchInput.value = val; // Trik taruh kursor di akhir
</script>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border d-flex justify-content-between mb-3">
                    <span id="d_waktu"></span>
                    <span id="d_status_pesanan" class="badge"></span>
                </div>
                <div class="mb-3"><label class="small text-muted fw-bold">Pelanggan</label><div id="d_pelanggan" class="fw-bold"></div></div>
                <table class="table table-sm table-borderless bg-light rounded">
                    <tbody id="d_items"></tbody>
                    <tfoot class="border-top">
                        <tr class="text-danger" id="row_diskon_modal" style="display:none">
                            <td colspan="2">Diskon</td><td class="text-end fw-bold" id="d_diskon"></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="fw-bold">TOTAL</td><td class="text-end fw-bold fs-5 text-primary" id="d_total"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(id) {
    fetch('api/get_detail_transaksi.php?id=' + id).then(r => r.json()).then(data => {
        if(data.status === 'success') {
            let h = data.header;
            document.getElementById('d_waktu').innerText = h.created_at;
            document.getElementById('d_pelanggan').innerText = h.nama_pelanggan + ' (Meja ' + h.nomor_meja + ')';
            document.getElementById('d_total').innerText = 'Rp ' + parseInt(h.total_harga).toLocaleString('id-ID');
            document.getElementById('d_status_pesanan').innerText = h.status_pembayaran.toUpperCase();
            document.getElementById('d_status_pesanan').className = 'badge ' + (h.status_pembayaran=='settlement'?'bg-success':'bg-warning');
            
            let diskon = parseInt(h.diskon);
            if(diskon > 0) {
                document.getElementById('row_diskon_modal').style.display = 'table-row';
                document.getElementById('d_diskon').innerText = '- Rp ' + diskon.toLocaleString('id-ID');
            } else {
                document.getElementById('row_diskon_modal').style.display = 'none';
            }

            let html = '';
            data.items.forEach(i => { html += `<tr><td>${i.nama_menu}</td><td class="text-end">${i.qty}x</td><td class="text-end">Rp ${parseInt(i.subtotal).toLocaleString()}</td></tr>`; });
            document.getElementById('d_items').innerHTML = html;
            
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
    });
}
</script>

<?php include '../layouts/admin/footer.php'; ?>