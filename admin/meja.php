<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Meja & Kursi";
$active_menu = "meja";

// --- PROSES 1: TAMBAH MEJA OTOMATIS ---
if(isset($_POST['tambah_meja_otomatis'])) {
    $jumlah = (int) $_POST['jumlah_penambahan'];
    $cabang = $_SESSION['cabang_id'] ?? $_POST['cabang_id']; 
    
    if ($jumlah < 1) {
        $_SESSION['swal'] = ['icon'=>'warning', 'title'=>'Gagal', 'text'=>'Jumlah minimal 1'];
    } else {
        // Cari Nomor Terakhir
        $q_last = $koneksi->query("SELECT MAX(CAST(nomor_meja AS UNSIGNED)) as max_no FROM meja WHERE cabang_id = '$cabang'");
        $row_last = $q_last->fetch_assoc();
        $start_no = ($row_last['max_no']) ? $row_last['max_no'] + 1 : 1; 
        
        $stmt = $koneksi->prepare("INSERT INTO meja (cabang_id, nomor_meja, qr_token, status) VALUES (?, ?, ?, 'kosong')");
        
        for ($i = 0; $i < $jumlah; $i++) {
            $nomor_baru = (string)($start_no + $i);
            $token = md5(uniqid(rand(), true));
            $stmt->bind_param("iss", $cabang, $nomor_baru, $token);
            $stmt->execute();
        }
        
        $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Selesai!', 'text' => "$jumlah Meja berhasil ditambahkan."];
    }
    header("Location: meja"); exit;
}

// --- PROSES 2: HAPUS MEJA ---
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek = $koneksi->query("SELECT status FROM meja WHERE id = '$id'")->fetch_assoc();
    
    if ($cek['status'] == 'terisi') {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Meja sedang digunakan! Kosongkan dulu.'];
    } else {
        $koneksi->query("DELETE FROM meja WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Meja berhasil dihapus'];
    }
    header("Location: meja"); exit;
}

// Filter Cabang
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$where = "";
$cabang_label = "Semua Cabang";

if ($_SESSION['level'] != 'admin' || (isset($_SESSION['view_cabang_id']) && $_SESSION['view_cabang_id'] != 'pusat')) {
    $target = $_SESSION['view_cabang_id'] ?? $cabang_id;
    $where = "WHERE m.cabang_id = '$target'";
    $c = $koneksi->query("SELECT nama_cabang FROM cabang WHERE id = '$target'")->fetch_assoc();
    $cabang_label = $c['nama_cabang'] ?? 'Cabang Anda';
}

$sql = "SELECT m.*, c.nama_cabang 
        FROM meja m 
        LEFT JOIN cabang c ON m.cabang_id = c.id 
        $where 
        ORDER BY c.id ASC, CAST(m.nomor_meja AS UNSIGNED) ASC";
$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 text-primary fw-bold"><i class="fas fa-chair me-2"></i>Manajemen Meja</h4>
        <small class="text-muted">Lokasi: <strong><?= $cabang_label ?></strong> (Total: <?= $data->num_rows ?> Meja)</small>
    </div>
    
    <div class="d-flex gap-2">
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus-circle me-2"></i> Tambah Kursi/Meja
        </button>
        <a href="cetak_qr_all.php" target="_blank" class="btn btn-dark fw-bold shadow-sm">
            <i class="fas fa-print me-2"></i> Cetak Semua QR
        </a>
    </div>
</div>

<div class="row g-3">
    <?php if($data->num_rows == 0): ?>
        <div class="col-12 text-center py-5 text-muted bg-white rounded shadow-sm">
            <h5>Belum ada meja.</h5>
        </div>
    <?php endif; ?>

    <?php while($row = $data->fetch_assoc()): ?>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100 position-relative card-hover">
            <div class="position-absolute top-0 start-0 w-100 p-2 d-flex justify-content-between">
                <small class="text-muted fw-bold" style="font-size: 0.7rem;">ID: <?= $row['id'] ?></small>
                <?php if($row['status']=='kosong'): ?>
                    <span class="badge bg-success rounded-pill">Free</span>
                <?php else: ?>
                    <span class="badge bg-danger rounded-pill">Busy</span>
                <?php endif; ?>
            </div>

            <div class="card-body text-center pt-4 pb-2 px-2 d-flex flex-column align-items-center">
                <div class="mb-2 mt-2 position-relative">
                    <i class="fas fa-chair fa-3x <?= $row['status']=='kosong' ? 'text-secondary' : 'text-danger' ?>"></i>
                    <h3 class="fw-bold mb-0 mt-2"><?= $row['nomor_meja'] ?></h3>
                </div>
                <small class="text-muted text-truncate w-100 mb-3"><?= $row['nama_cabang'] ?></small>
                
                <div id="qrcode-<?= $row['id'] ?>" 
                     class="mb-2 border p-1 bg-white rounded zoom-qr" 
                     style="cursor: zoom-in;" 
                     title="Klik untuk memperbesar"
                     onclick="zoomQr('<?= $row['qr_token'] ?>', 'Meja <?= $row['nomor_meja'] ?>')">
                </div>
                
                <div class="d-grid gap-1 w-100 mt-auto">
                    <?php if($row['status']=='terisi'): ?>
                        <button class="btn btn-warning btn-sm text-white fw-bold py-1" onclick="kosongkanMeja(<?= $row['id'] ?>)">
                            <i class="fas fa-eraser"></i> Kosongkan
                        </button>
                    <?php else: ?>
                        <a href="meja?hapus=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm py-1" onclick="return confirm('Hapus meja ini?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script>
            new QRCode(document.getElementById("qrcode-<?= $row['id'] ?>"), {
                text: "<?= BASE_URL ?>/penjualan/index.php?token=<?= $row['qr_token'] ?>",
                width: 60, height: 60, correctLevel : QRCode.CorrectLevel.L
            });
        </script>
    </div>
    <?php endwhile; ?>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tambah Meja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if($_SESSION['level'] == 'admin' && !isset($_SESSION['cabang_id'])): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Cabang</label>
                    <select name="cabang_id" class="form-select" required>
                        <?php 
                        $cabs = $koneksi->query("SELECT * FROM cabang");
                        while($c = $cabs->fetch_assoc()) { echo "<option value='".$c['id']."'>".$c['nama_cabang']."</option>"; }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Jumlah Meja Baru</label>
                    <input type="number" name="jumlah_penambahan" class="form-control text-center fw-bold fs-5" value="1" min="1" required>
                    <small class="text-muted">Nomor meja akan otomatis berlanjut.</small>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" name="tambah_meja_otomatis" class="btn btn-primary fw-bold w-100">Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; transition: 0.3s; }
</style>

<script>
// Fungsi Zoom QR Code
function zoomQr(token, nama) {
    Swal.fire({
        title: nama,
        html: '<div id="qr-zoom" class="d-flex justify-content-center py-3"></div><p class="small text-muted mt-2">Scan QR ini untuk memesan</p>',
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {
            // Generate QR Besar di dalam Modal
            new QRCode(document.getElementById("qr-zoom"), {
                text: "<?= BASE_URL ?>/penjualan/index.php?token=" + token,
                width: 200, height: 200,
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    });
}

function kosongkanMeja(id) {
    Swal.fire({
        title: 'Reset Meja?', text: "Meja akan dikosongkan.", icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, Reset'
    }).then((res) => {
        if(res.isConfirmed) {
            let fd = new FormData(); fd.append('action', 'kosongkan_meja'); fd.append('id', id);
            fetch('api/transaksi_action.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if(d.status === 'success') location.reload();
            });
        }
    });
}
</script>

<?php include '../layouts/admin/footer.php'; ?>