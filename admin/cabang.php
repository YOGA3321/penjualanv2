<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Cabang";
$active_menu = "cabang";

// --- TAMBAH ---
if(isset($_POST['tambah_cabang'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_cabang']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    // Default jam 10 - 22
    $stmt = $koneksi->prepare("INSERT INTO cabang (nama_cabang, alamat, jam_buka, jam_tutup) VALUES (?, ?, '10:00:00', '22:00:00')");
    $stmt->bind_param("ss", $nama, $alamat);
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Cabang ditambahkan'];
    header("Location: cabang"); exit;
}

// --- EDIT (UPDATE JAM JUGA) ---
if(isset($_POST['edit_cabang'])) {
    $id = $_POST['id_cabang'];
    $nama = $koneksi->real_escape_string($_POST['nama_cabang']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    $buka = $_POST['jam_buka'];
    $tutup = $_POST['jam_tutup'];
    
    $stmt = $koneksi->prepare("UPDATE cabang SET nama_cabang=?, alamat=?, jam_buka=?, jam_tutup=? WHERE id=?");
    $stmt->bind_param("ssssi", $nama, $alamat, $buka, $tutup, $id);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Data cabang diperbarui'];
    header("Location: cabang"); exit;
}

// ... (Logika Set Pusat & Hapus tetap sama seperti sebelumnya) ...
if(isset($_GET['set_pusat'])) {
    $id = $_GET['set_pusat'];
    $koneksi->query("UPDATE cabang SET is_pusat = 0");
    $koneksi->query("UPDATE cabang SET is_pusat = 1 WHERE id = '$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Sukses', 'text'=>'Pusat diubah'];
    header("Location: cabang"); exit;
}
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $koneksi->query("DELETE FROM cabang WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Cabang dihapus'];
    } catch (Exception $e) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Cabang ini masih memiliki data terkait.'];
    }
    header("Location: cabang"); exit;
}

$data = $koneksi->query("SELECT * FROM cabang ORDER BY is_pusat DESC, id ASC");

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-store-alt me-2"></i>Daftar Cabang</h4>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Tambah Cabang
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Nama Cabang</th>
                        <th>Alamat</th>
                        <th>Jam Operasional</th>
                        <th class="text-center">Status</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($data->num_rows > 0): ?>
                    <?php while($row = $data->fetch_assoc()): 
                        $jsonCabang = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td class="px-4 fw-bold">
                            <?= htmlspecialchars($row['nama_cabang']) ?>
                            <?php if($row['is_pusat']): ?>
                                <span class="badge bg-warning text-dark ms-2">PUSAT</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($row['alamat']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= date('H:i', strtotime($row['jam_buka'])) ?> - <?= date('H:i', strtotime($row['jam_tutup'])) ?>
                            </span>
                            <?php if(!$row['is_open']): ?>
                                <span class="badge bg-danger ms-1">TUTUP</span>
                            <?php else: ?>
                                <span class="badge bg-success ms-1">BUKA</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if(!$row['is_pusat']): ?>
                                <a href="cabang?set_pusat=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm">Set Pusat</a>
                            <?php endif; ?>
                        </td>
                        <td class="text-end px-4">
                            <button class="btn btn-sm btn-info text-white me-1" onclick='openEditModal(<?= $jsonCabang ?>)'><i class="fas fa-edit"></i></button>
                            <?php if(!$row['is_pusat']): ?>
                                <a href="cabang?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5">Belum ada cabang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Cabang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label>Nama Cabang</label><input type="text" name="nama_cabang" class="form-control" required></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah_cabang" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Cabang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id_cabang" id="edit_id">
                <div class="mb-3"><label>Nama Cabang</label><input type="text" name="nama_cabang" id="edit_nama" class="form-control" required></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" id="edit_alamat" class="form-control" rows="3" required></textarea></div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>Jam Buka</label>
                        <input type="time" name="jam_buka" id="edit_buka" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label>Jam Tutup</label>
                        <input type="time" name="jam_tutup" id="edit_tutup" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_cabang" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<script>
function openEditModal(cabang) {
    document.getElementById('edit_id').value = cabang.id;
    document.getElementById('edit_nama').value = cabang.nama_cabang;
    document.getElementById('edit_alamat').value = cabang.alamat;
    document.getElementById('edit_buka').value = cabang.jam_buka; // Format HH:mm:ss otomatis dipotong browser jadi HH:mm
    document.getElementById('edit_tutup').value = cabang.jam_tutup;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include '../layouts/admin/footer.php'; ?>