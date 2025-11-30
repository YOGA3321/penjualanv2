<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Riwayat Transaksi";
$active_menu = "riwayat";

$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';
if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}

// Query Data
$sql = "SELECT t.*, m.nomor_meja, u.nama as nama_kasir 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        LEFT JOIN users u ON t.user_id = u.id";

if ($level != 'admin' || isset($_SESSION['view_cabang_id'])) {
    $sql .= " WHERE m.cabang_id = '$cabang_id'";
}
$sql .= " ORDER BY t.created_at DESC LIMIT 50";

$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">Transaksi Terbaru</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Waktu</th>
                        <th>Meja</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($data->num_rows > 0): ?>
                        <?php while($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold">#<?= $row['id'] ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                                <small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                            </td>
                            <td><span class="badge bg-dark">Meja <?= $row['nomor_meja'] ?></span></td>
                            <td><?= $row['nama_pelanggan'] ?></td>
                            <td class="fw-bold text-primary">Rp <?= number_format($row['total_harga']) ?></td>
                            <td>
                                <?php 
                                    $st = $row['status_pembayaran'];
                                    $bg = ($st=='settlement') ? 'success' : 'warning';
                                    echo "<span class='badge bg-$bg'>".strtoupper($st)."</span>";
                                ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick="showDetail('<?= $row['uuid'] ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="../penjualan/struk.php?uuid=<?= $row['uuid'] ?>&print=true" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
                        <span>Bayar Tunai</span>
                        <span id="d_bayar"></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Kembalian</span>
                        <span id="d_kembalian"></span>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    Status Pesanan: <span id="d_status_pesanan" class="badge"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(uuid) {
    // Loading state
    Swal.fire({title: 'Memuat...', didOpen: () => Swal.showLoading()});

    fetch(`api/get_detail_transaksi.php?uuid=${uuid}`)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.status === 'success') {
            const h = data.header;
            
            // Isi Data Modal
            document.getElementById('d_id').innerText = '#' + h.id;
            document.getElementById('d_waktu').innerText = h.created_at;
            document.getElementById('d_pelanggan').innerText = h.nama_pelanggan + ' (Meja ' + h.nomor_meja + ')';
            document.getElementById('d_total').innerText = 'Rp ' + parseInt(h.total_harga).toLocaleString('id-ID');
            document.getElementById('d_bayar').innerText = 'Rp ' + parseInt(h.uang_bayar).toLocaleString('id-ID');
            document.getElementById('d_kembalian').innerText = 'Rp ' + parseInt(h.kembalian).toLocaleString('id-ID');
            
            // Status Badge
            const badge = document.getElementById('d_status_pesanan');
            badge.innerText = h.status_pesanan.toUpperCase();
            badge.className = 'badge ' + (h.status_pesanan === 'selesai' ? 'bg-success' : 'bg-warning');

            // Isi Items
            let htmlItems = '';
            data.items.forEach(item => {
                htmlItems += `
                    <tr>
                        <td>${item.nama_menu}</td>
                        <td class="text-end">${item.qty}x</td>
                        <td class="text-end">Rp ${parseInt(item.subtotal).toLocaleString()}</td>
                    </tr>
                `;
            });
            document.getElementById('d_items').innerHTML = htmlItems;

            // Tampilkan Modal
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => Swal.fire('Error', 'Gagal mengambil data', 'error'));
}
</script>

<?php include '../layouts/admin/footer.php'; ?>