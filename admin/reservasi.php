<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] == 'pelanggan') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Data Reservasi";
$active_menu = "reservasi";

// --- LOGIKA CHECK-IN ---
if(isset($_GET['checkin'])) {
    $id = $_GET['checkin'];
    // Ubah status jadi checkin
    $koneksi->query("UPDATE reservasi SET status = 'checkin' WHERE id = '$id'");
    
    // Opsional: Ubah status meja jadi terisi? 
    // Sebaiknya jangan dulu, biarkan mereka scan QR di meja untuk validasi akhir.
    // Tapi status 'checkin' di reservasi sudah menandakan mereka hadir.
    
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Check-in Berhasil', 'text'=>'Pelanggan telah hadir. Silakan arahkan ke meja.'];
    header("Location: reservasi.php"); exit;
}

// --- LOGIKA BATAL ---
if(isset($_GET['batal'])) {
    $id = $_GET['batal'];
    $koneksi->query("UPDATE reservasi SET status = 'batal' WHERE id = '$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Dibatalkan', 'text'=>'Reservasi dibatalkan.'];
    header("Location: reservasi.php"); exit;
}

// FILTER CABANG
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'];
$where = "";

if ($level == 'admin') {
    $view = $_SESSION['view_cabang_id'] ?? 'pusat';
    if($view != 'pusat') $where = "WHERE m.cabang_id = '$view'";
} else {
    $where = "WHERE m.cabang_id = '$cabang_id'";
}

// QUERY DATA
$sql = "SELECT r.*, u.nama as pelanggan, u.no_hp, m.nomor_meja, c.nama_cabang 
        FROM reservasi r
        JOIN users u ON r.user_id = u.id
        JOIN meja m ON r.meja_id = m.id
        JOIN cabang c ON m.cabang_id = c.id
        $where
        ORDER BY r.waktu_reservasi DESC";
$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-check me-2"></i>Daftar Reservasi</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Pelanggan</th>
                        <th>Meja / Cabang</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $data->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= date('d M Y', strtotime($row['waktu_reservasi'])) ?></div>
                            <div class="text-primary"><?= date('H:i', strtotime($row['waktu_reservasi'])) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['pelanggan']) ?></div>
                            <small class="text-muted"><?= $row['no_hp'] ? $row['no_hp'] : '-' ?></small>
                        </td>
                        <td>
                            <span class="badge bg-dark">Meja <?= $row['nomor_meja'] ?></span><br>
                            <small class="text-muted"><?= $row['nama_cabang'] ?></small>
                        </td>
                        <td>
                            <?php
                                $st = $row['status'];
                                if($st=='pending') echo '<span class="badge bg-warning text-dark">Menunggu</span>';
                                elseif($st=='checkin') echo '<span class="badge bg-success">Hadir (Check-in)</span>';
                                elseif($st=='batal') echo '<span class="badge bg-danger">Batal</span>';
                                else echo '<span class="badge bg-secondary">Selesai</span>';
                            ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if($st == 'pending'): ?>
                                <a href="reservasi.php?checkin=<?= $row['id'] ?>" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Konfirmasi kedatangan pelanggan?')">
                                    <i class="fas fa-check me-1"></i> Check-in
                                </a>
                                <a href="reservasi.php?batal=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan reservasi ini?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>