<?php
session_start();
require_once '../auth/koneksi.php';

// 1. CEK AKSES: HANYA ADMIN YANG BOLEH MASUK
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: index"); // Lempar paksa ke index
    exit;
}

$page_title = "Manajemen Cabang";
$active_menu = "cabang";

// LOGIKA TAMBAH CABANG
if (isset($_POST['tambah_cabang'])) {
    $nama   = htmlspecialchars($_POST['nama_cabang']);
    $alamat = htmlspecialchars($_POST['alamat']);
    
    $stmt = $koneksi->prepare("INSERT INTO cabang (nama_cabang, alamat) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama, $alamat);
    
    if ($stmt->execute()) {
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Cabang baru ditambahkan'];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    }
    header("Location: cabang");
    exit;
}

// LOGIKA HAPUS
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah cabang ini adalah pusat (biasanya ID 1 atau is_pusat=1)
    // Opsional: Tambahkan proteksi agar cabang pusat tidak bisa dihapus sembarangan
    
    $koneksi->query("DELETE FROM cabang WHERE id = '$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Data cabang berhasil dihapus'];
    
    header("Location: cabang");
    exit;
}

// AMBIL DATA
$data = $koneksi->query("SELECT * FROM cabang ORDER BY id DESC");

// HEADER
$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cabangModal">
    <i class="fas fa-plus me-2"></i>Tambah Cabang
</button>';

include '../layouts/admin/header.php';
?>

<div class="row">
    <?php while($row = $data->fetch_assoc()): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card branch-card h-100">
            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 150px;">
                <i class="fas fa-store fa-3x"></i>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= $row['nama_cabang'] ?></h5>
                <p class="card-text text-muted small"><i class="fas fa-map-marker-alt me-1"></i> <?= $row['alamat'] ?></p>
                
                <?php if(isset($row['is_pusat']) && $row['is_pusat'] == 1): ?>
                    <span class="badge bg-warning text-dark">Cabang Utama</span>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-0 text-end">
                <a href="javascript:void(0);" onclick="confirmDelete('<?= $row['id'] ?>')" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash"></i> Hapus
                </a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<div class="modal fade" id="cabangModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Cabang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Cabang</label>
                        <input type="text" name="nama_cabang" class="form-control" required placeholder="Cth: Cabang Jakarta Selatan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_cabang" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Cabang?',
            text: "Semua data meja dan history terkait cabang ini akan ikut terhapus!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?hapus=" + id;
            }
        })
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>

<?php if (isset($_SESSION['swal'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['swal']['icon'] ?>',
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        timer: 2500,
        showConfirmButton: false
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>