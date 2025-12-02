<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Cabang";
$active_menu = "cabang";

// --- TAMBAH CABANG ---
if(isset($_POST['tambah_cabang'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_cabang']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    $stmt = $koneksi->prepare("INSERT INTO cabang (nama_cabang, alamat) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama, $alamat);
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Cabang ditambahkan'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    header("Location: cabang"); exit;
}

// --- EDIT CABANG ---
if(isset($_POST['edit_cabang'])) {
    $id = $_POST['id_cabang'];
    $nama = $koneksi->real_escape_string($_POST['nama_cabang']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    $stmt = $koneksi->prepare("UPDATE cabang SET nama_cabang = ?, alamat = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nama, $alamat, $id);
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Data cabang diperbarui'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    header("Location: cabang"); exit;
}

// --- SET PUSAT ---
if(isset($_GET['set_pusat'])) {
    $id = $_GET['set_pusat'];
    $koneksi->query("UPDATE cabang SET is_pusat = 0"); // Reset semua
    $koneksi->query("UPDATE cabang SET is_pusat = 1 WHERE id = '$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Sukses', 'text'=>'Cabang Pusat diubah'];
    header("Location: cabang"); exit;
}

// --- HAPUS ---
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek = $koneksi->query("SELECT is_pusat FROM cabang WHERE id = '$id'")->fetch_assoc();
    if($cek['is_pusat'] == 1) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Ditolak', 'text'=>'Cabang Pusat tidak boleh dihapus!'];
    } else {
        // Cek relasi dulu (Opsional, tapi disarankan)
        // Disini kita langsung hapus (hati-hati data terkait akan hilang/error jika tidak CASCADE)
        try {
            $koneksi->query("DELETE FROM cabang WHERE id = '$id'");
            $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Cabang dihapus'];
        } catch (Exception $e) {
            $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Cabang ini masih memiliki data terkait.'];
        }
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
                                    <span class="badge bg-warning text-dark ms-2"><i class="fas fa-crown me-1"></i>PUSAT</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($row['alamat']) ?></td>
                            <td class="text-center">
                                <?php if($row['is_pusat']): ?>
                                    <span class="badge bg-success">Utama</span>
                                <?php else: ?>
                                    <a href="cabang?set_pusat=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Set sebagai PUSAT?')">
                                        Set Utama
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick='openEditModal(<?= $jsonCabang ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if(!$row['is_pusat']): ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>, '<?= $row['nama_cabang'] ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada cabang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label>Nama Cabang</label><input type="text" name="nama_cabang" class="form-control" required></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="tambah_cabang" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_cabang" id="edit_id">
                <div class="mb-3"><label>Nama Cabang</label><input type="text" name="nama_cabang" id="edit_nama" class="form-control" required></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" id="edit_alamat" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_cabang" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(cabang) {
    document.getElementById('edit_id').value = cabang.id;
    document.getElementById('edit_nama').value = cabang.nama_cabang;
    document.getElementById('edit_alamat').value = cabang.alamat;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus ' + nama + '?', text: "Data transaksi terkait mungkin hilang!", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
    }).then((r) => { if (r.isConfirmed) window.location.href = "?hapus=" + id; })
}
</script>

<?php include '../layouts/admin/footer.php'; ?>