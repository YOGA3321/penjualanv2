<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] == 'pelanggan') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Data Reservasi";
$active_menu = "reservasi";

// --- LOGIKA CHECK-IN ---
if(isset($_GET['checkin'])) {
    $id = $_GET['checkin'];
    $koneksi->query("UPDATE reservasi SET status = 'checkin' WHERE id = '$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Check-in Berhasil', 'text'=>'Pelanggan telah hadir.'];
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
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-check me-2"></i>Daftar Reservasi</h5>
        
        <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 rounded-pill animate-pulse">
            <i class="fas fa-circle me-1" style="font-size: 8px;"></i> Realtime Active
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelReservasi">
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
                    <?php if($data->num_rows > 0): ?>
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
                                    <a href="reservasi.php?checkin=<?= $row['id'] ?>" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="return confirm('Konfirmasi kedatangan pelanggan?')">
                                        <i class="fas fa-check me-1"></i> Check-in
                                    </a>
                                    <a href="reservasi.php?batal=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Batalkan reservasi ini?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data reservasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<audio id="notifSound" src="../assets/audio/notification.mp3" preload="auto"></audio>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Menghubungkan ke API SSE Reservasi
    const source = new EventSource("api/sse_reservasi.php");
    const notifSound = document.getElementById('notifSound');

    source.onmessage = function(event) {
        if(event.data == "update_reservasi") {
            console.log("Ada reservasi baru/update!");
            
            // Mainkan suara jika file ada
            if(notifSound) {
                notifSound.play().catch(e => console.log("Audio play blocked"));
            }

            // Tampilkan Toast/Notifikasi kecil (Opsional)
            // alert("Data reservasi diperbarui!");

            // Reload halaman untuk refresh data
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    };

    source.onerror = function() {
        console.warn("SSE Error - Reconnecting...");
        // Browser akan otomatis mencoba reconnect
    };
});
</script>

<style>
/* Animasi kedip untuk indikator realtime */
@keyframes pulse-green {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
.animate-pulse {
    animation: pulse-green 2s infinite;
}
</style>

<?php include '../layouts/admin/footer.php'; ?>