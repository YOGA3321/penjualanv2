<?php
// admin/cabang.php
require_once '../auth/koneksi.php';

$page_title = "Manajemen Cabang";
$active_menu = "cabang";

// LOGIKA TAMBAH CABANG
if (isset($_POST['tambah_cabang'])) {
    $nama   = htmlspecialchars($_POST['nama_cabang']);
    $alamat = htmlspecialchars($_POST['alamat']);
    
    $stmt = $koneksi->prepare("INSERT INTO cabang (nama_cabang, alamat) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama, $alamat);
    
    if ($stmt->execute()) {
        $sukses = "Cabang berhasil ditambahkan!";
    } else {
        $error = "Gagal: " . $koneksi->error;
    }
}

// LOGIKA HAPUS
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM cabang WHERE id = '$id'");
    header("Location: cabang");
    exit;
}

// AMBIL DATA
$data = $koneksi->query("SELECT * FROM cabang ORDER BY id DESC");

// HEADER & TOMBOL ACTION
$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cabangModal">
    <i class="fas fa-plus me-2"></i>Tambah Cabang
</button>';

include '../layouts/admin/header.php';
?>

<?php if(isset($sukses)): ?><div class="alert alert-success"><?= $sukses ?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

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
            </div>
            <div class="card-footer bg-white border-0 text-end">
                <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus cabang ini beserta mejanya?')">
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

<?php include '../layouts/admin/footer.php'; ?>