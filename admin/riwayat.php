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

// QUERY DATA TRANSAKSI (SKELETON)
// Karena tabel transaksi belum diisi data real, ini struktur dasarnya
$sql = "SELECT t.*, m.nomor_meja, u.nama as nama_kasir 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        LEFT JOIN users u ON t.user_id = u.id";

if ($level != 'admin' || isset($_SESSION['view_cabang_id'])) {
    // Filter transaksi berdasarkan cabang meja
    $sql .= " WHERE m.cabang_id = '$cabang_id'";
}
$sql .= " ORDER BY t.created_at DESC LIMIT 50";

$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Transaksi Terbaru</h6>
        <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i> Cetak Laporan</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID Transaksi</th>
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
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td>Meja <?= $row['nomor_meja'] ?></td>
                            <td><?= $row['nama_pelanggan'] ?></td>
                            <td class="fw-bold text-primary">Rp <?= number_format($row['total_harga']) ?></td>
                            <td>
                                <?php 
                                    $st = $row['status_pembayaran'];
                                    $badge = ($st=='settlement')?'success':(($st=='pending')?'warning':'danger');
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= strtoupper($st) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>