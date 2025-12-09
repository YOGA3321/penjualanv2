<?php
session_start();
// Allow Admin & Gudang
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'gudang' && $_SESSION['level'] != 'admin')) { 
    header("Location: ../login.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Manajemen Pemasok";
$active_menu = "pemasok";

// --- CRUD PEMASOK ---
if (isset($_POST['tambah'])) {
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $kontak = $koneksi->real_escape_string($_POST['kontak']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    
    $koneksi->query("INSERT INTO pemasok (nama_pemasok, kontak, alamat) VALUES ('$nama', '$kontak', '$alamat')");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Pemasok ditambahkan'];
    header("Location: pemasok.php"); exit;
}

if (isset($_POST['edit'])) {
    $id = $_POST['edit_id'];
    $nama = $koneksi->real_escape_string($_POST['edit_nama']);
    $kontak = $koneksi->real_escape_string($_POST['edit_kontak']);
    $alamat = $koneksi->real_escape_string($_POST['edit_alamat']);
    
    $koneksi->query("UPDATE pemasok SET nama_pemasok='$nama', kontak='$kontak', alamat='$alamat' WHERE id='$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Data pemasok diperbarui'];
    header("Location: pemasok.php"); exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $koneksi->query("DELETE FROM pemasok WHERE id='$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Pemasok dihapus'];
    } catch (Exception $e) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Pemasok tidak bisa dihapus karena memiliki riwayat transaksi.'];
    }
    header("Location: pemasok.php"); exit;
}

$data = $koneksi->query("SELECT * FROM pemasok ORDER BY nama_pemasok ASC");

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-primary"><i class="fas fa-truck-moving me-2"></i>Manajemen Pemasok</h4>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Tambah Pemasok
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Nama Pemasok</th>
                        <th>Kontak / HP</th>
                        <th>Alamat</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($data->num_rows > 0): ?>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td class="px-4 fw-bold"><?= $row['nama_pemasok'] ?></td>
                            <td><?= $row['kontak'] ?: '-' ?></td>
                            <td class="small text-muted" style="max-width: 300px;"><?= $row['alamat'] ?: '-' ?></td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick="openEdit(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="fas fa-edit"></i></button>
                                <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus pemasok ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada data pemasok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Pemasok</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label>Nama Pemasok</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-3"><label>Kontak / No. HP</label><input type="text" name="kontak" class="form-control"></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Pemasok</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="mb-3"><label>Nama Pemasok</label><input type="text" name="edit_nama" id="edit_nama" class="form-control" required></div>
                <div class="mb-3"><label>Kontak / No. HP</label><input type="text" name="edit_kontak" id="edit_kontak" class="form-control"></div>
                <div class="mb-3"><label>Alamat</label><textarea name="edit_alamat" id="edit_alamat" class="form-control"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<script>
function openEdit(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nama').value = data.nama_pemasok;
    document.getElementById('edit_kontak').value = data.kontak;
    document.getElementById('edit_alamat').value = data.alamat;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>

<?php include '../layouts/admin/footer.php'; ?>
