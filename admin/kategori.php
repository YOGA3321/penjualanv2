<?php
require_once '../auth/koneksi.php';

$page_title = "Kategori Menu";
$active_menu = "kategori";

// TAMBAH KATEGORI
if (isset($_POST['tambah'])) {
    $nama = htmlspecialchars($_POST['nama_kategori']);
    if ($koneksi->query("INSERT INTO kategori_menu (nama_kategori) VALUES ('$nama')")) {
        $sukses = "Kategori berhasil disimpan.";
    } else {
        $error = "Gagal menyimpan.";
    }
}

// EDIT KATEGORI
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = htmlspecialchars($_POST['nama_kategori']);
    $koneksi->query("UPDATE kategori_menu SET nama_kategori='$nama' WHERE id='$id'");
    $sukses = "Kategori berhasil diupdate.";
}

// HAPUS KATEGORI
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM kategori_menu WHERE id='$id'");
    header("Location: kategori");
    exit;
}

$data = $koneksi->query("SELECT * FROM kategori_menu ORDER BY id DESC");

$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="fas fa-plus me-2"></i>Tambah Kategori
</button>';

include '../layouts/admin/header.php';
?>

<?php if(isset($sukses)): ?><div class="alert alert-success"><?= $sukses ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width: 800px;">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Kategori</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($row = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-bold"><?= $row['nama_kategori'] ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal<?= $row['id'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini? Menu didalamnya mungkin akan error.')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>

                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header"><h5 class="modal-title">Edit Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <label class="form-label">Nama Kategori</label>
                                    <input type="text" name="nama_kategori" class="form-control" value="<?= $row['nama_kategori'] ?>" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Kategori Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" required placeholder="Cth: Minuman">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>