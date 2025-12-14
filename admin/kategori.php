<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Kategori Menu";
$active_menu = "kategori";

$level = $_SESSION['level'];
$view_cabang_id = 'pusat';

if ($level == 'admin') {
    $view_cabang_id = $_SESSION['view_cabang_id'] ?? 'pusat';
} else {
    $view_cabang_id = $_SESSION['cabang_id'];
}
$is_global = ($view_cabang_id == 'pusat');

// --- CRUD ---
if(isset($_POST['tambah_kategori'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_kategori']);
    $cabang = ($_POST['cabang_id'] === 'NULL' || $_POST['cabang_id'] === '') ? NULL : $_POST['cabang_id'];
    
    $stmt = $koneksi->prepare("INSERT INTO kategori_menu (nama_kategori, cabang_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama, $cabang);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Kategori ditambahkan'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    header("Location: kategori"); exit;
}

if(isset($_POST['edit_kategori'])) {
    $id = $_POST['id_kategori'];
    $nama = $koneksi->real_escape_string($_POST['nama_kategori']);
    $cabang = ($_POST['cabang_id'] === 'NULL' || $_POST['cabang_id'] === '') ? NULL : $_POST['cabang_id'];
    
    $stmt = $koneksi->prepare("UPDATE kategori_menu SET nama_kategori = ?, cabang_id = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nama, $cabang, $id);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Kategori diperbarui'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    header("Location: kategori"); exit;
}

if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Soft delete tidak perlu untuk kategori, tapi cek error
    try {
        $koneksi->query("DELETE FROM kategori_menu WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Kategori dihapus'];
    } catch (mysqli_sql_exception $e) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Tidak bisa dihapus karena masih dipakai menu.'];
    }
    header("Location: kategori"); exit;
}

// --- QUERY LIST ---
$sql = "SELECT k.*, c.nama_cabang FROM kategori_menu k LEFT JOIN cabang c ON k.cabang_id = c.id";
if (!$is_global) {
    $sql .= " WHERE (k.cabang_id = '$view_cabang_id' OR k.cabang_id IS NULL)";
}
$sql .= " ORDER BY k.cabang_id ASC, k.nama_kategori ASC";
$data = $koneksi->query($sql);

// --- LIST CABANG ---
$sql_cab = "SELECT * FROM cabang";
if (!$is_global) { $sql_cab .= " WHERE id = '$view_cabang_id'"; }
$q_cab = $koneksi->query($sql_cab);
$list_cabang = []; while($c = $q_cab->fetch_assoc()) $list_cabang[] = $c;

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-tags me-2"></i>Kategori Menu</h4>
        <small class="text-muted">Mode: <strong><?= $is_global ? 'Semua Cabang' : 'Cabang Terpilih' ?></strong></small>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Tambah Kategori
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">No</th>
                        <th>Nama Kategori</th>
                        <th>Lokasi Cabang</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($data->num_rows > 0): ?>
                        <?php $no=1; while($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4"><?= $no++ ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td>
                                <?php if($row['cabang_id']): ?>
                                    <span class="badge bg-info text-dark"><?= $row['nama_cabang'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">GLOBAL</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-info text-white me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>, '<?= $row['nama_kategori'] ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada kategori.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Reset Pointer
if($data->num_rows > 0):
    $data->data_seek(0);
    while($row = $data->fetch_assoc()):
?>
<div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_kategori" value="<?= $row['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" value="<?= htmlspecialchars($row['nama_kategori']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Berlaku di Cabang</label>
                    <select name="cabang_id" class="form-select">
                        <option value="NULL" <?= $row['cabang_id'] == NULL ? 'selected' : '' ?>>-- GLOBAL --</option>
                        <?php 
                        // Untuk Edit, kita tampilkan semua cabang agar bisa dipindahkan
                        // Meskipun sedang filter Jogja, admin mungkin ingin memindahkannya ke Surabaya
                        $all_cabs = $koneksi->query("SELECT * FROM cabang");
                        while($ac = $all_cabs->fetch_assoc()): ?>
                            <option value="<?= $ac['id'] ?>" <?= $row['cabang_id'] == $ac['id'] ? 'selected' : '' ?>>
                                <?= $ac['nama_cabang'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_kategori" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endwhile; endif; ?>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label>Nama Kategori</label><input type="text" name="nama_kategori" class="form-control" required></div>
                <div class="mb-3">
                    <label>Lokasi Cabang</label>
                    <?php if ($is_global): ?>
                        <select name="cabang_id" class="form-select">
                            <option value="NULL">-- GLOBAL --</option>
                            <?php foreach($list_cabang as $c): echo "<option value='".$c['id']."'>".$c['nama_cabang']."</option>"; endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php $nama_cabang=""; foreach($list_cabang as $c){if($c['id']==$view_cabang_id)$nama_cabang=$c['nama_cabang'];} ?>
                        <input type="text" class="form-control bg-light" value="<?= $nama_cabang ?>" readonly>
                        <input type="hidden" name="cabang_id" value="<?= $view_cabang_id ?>">
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah_kategori" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus ' + nama + '?', text: "Akan gagal jika ada menu di dalamnya.", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
    }).then((r) => { if (r.isConfirmed) window.location.href = "?hapus=" + id; })
}
</script>

<?php include '../layouts/admin/footer.php'; ?>