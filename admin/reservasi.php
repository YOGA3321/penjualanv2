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
    $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = (SELECT meja_id FROM reservasi WHERE id='$id')");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Dibatalkan', 'text'=>'Reservasi dibatalkan.'];
    header("Location: reservasi.php"); exit;
}

// FILTER QUERY
$where = "";
if ($_SESSION['level'] == 'admin') {
    $view = $_SESSION['view_cabang_id'] ?? 'pusat';
    if($view != 'pusat') $where = "WHERE m.cabang_id = '$view'";
} else {
    $cid = $_SESSION['cabang_id'];
    $where = "WHERE m.cabang_id = '$cid'";
}

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
        <div id="liveIndicator" class="badge bg-light text-muted border">
            <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> Realtime Active
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableReservasi">
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
                                elseif($st=='checkin') echo '<span class="badge bg-success">Check-in</span>';
                                elseif($st=='batal') echo '<span class="badge bg-danger">Batal</span>';
                                else echo '<span class="badge bg-secondary">Selesai</span>';
                            ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if($st == 'pending'): ?>
                                <button class="btn btn-sm btn-success fw-bold me-1" onclick="konfirmasi('checkin', <?= $row['id'] ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="konfirmasi('batal', <?= $row['id'] ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada reservasi baru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// --- FITUR REALTIME (SSE) ---
let lastChecksum = null;

function initSSE() {
    // Hubungkan ke file SSE
    const evtSource = new EventSource("api/sse_reservasi.php");

    evtSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        // Buat checksum sederhana dari data (Total + ID Terakhir)
        // Agar kita tahu kalau ada data baru
        const currentChecksum = data.total + '-' + data.last_id + '-' + data.pending_count;

        if (lastChecksum && lastChecksum !== currentChecksum) {
            // Jika ada perubahan, Reload Halaman (Cara paling aman & mudah)
            // Atau bisa reload div tabel saja pakai fetch()
            location.reload(); 
        }
        lastChecksum = currentChecksum;
    };

    evtSource.onerror = function() {
        console.log("SSE Error, reconnecting...");
        evtSource.close();
        setTimeout(initSSE, 5000); // Coba lagi 5 detik
    };
}

// Jalankan SSE
document.addEventListener('DOMContentLoaded', initSSE);

// --- ALERT KONFIRMASI ---
function konfirmasi(aksi, id) {
    let title = aksi === 'checkin' ? 'Konfirmasi Kedatangan?' : 'Batalkan Reservasi?';
    let text = aksi === 'checkin' ? 'Pastikan pelanggan sudah hadir.' : 'Meja akan dikosongkan kembali.';
    let color = aksi === 'checkin' ? '#198754' : '#dc3545';
    let btnText = aksi === 'checkin' ? 'Ya, Check-in' : 'Ya, Batalkan';

    Swal.fire({
        title: title, text: text, icon: 'question',
        showCancelButton: true, confirmButtonColor: color, confirmButtonText: btnText
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `reservasi.php?${aksi}=${id}`;
        }
    });
}
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>Swal.fire({icon: '<?= $_SESSION['swal']['icon'] ?>', title: '<?= $_SESSION['swal']['title'] ?>', text: '<?= $_SESSION['swal']['text'] ?>'});</script>
<?php unset($_SESSION['swal']); endif; ?>

<?php include '../layouts/admin/footer.php'; ?>