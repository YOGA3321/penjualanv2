<?php
session_start();
// Cek Akses: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') { 
    header("Location: ../login.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Manajemen Cabang";
$active_menu = "cabang";

// --- LOGIKA: TAMBAH CABANG ---
if(isset($_POST['tambah_cabang'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_cabang']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    
    $stmt = $koneksi->prepare("INSERT INTO cabang (nama_cabang, alamat) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama, $alamat);
    
    if($stmt->execute()) {
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Cabang baru ditambahkan'];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    }
    header("Location: cabang"); exit;
}

// --- LOGIKA: EDIT CABANG ---
if(isset($_POST['edit_cabang'])) {
    $id = $_POST['id_cabang'];
    $nama = $koneksi->real_escape_string($_POST['nama_cabang']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    
    $stmt = $koneksi->prepare("UPDATE cabang SET nama_cabang = ?, alamat = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nama, $alamat, $id);
    
    if($stmt->execute()) {
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update Berhasil', 'text'=>'Data cabang diperbarui'];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    }
    header("Location: cabang"); exit;
}

// --- LOGIKA: SET CABANG UTAMA (PUSAT) ---
if(isset($_GET['set_pusat'])) {
    $id = $_GET['set_pusat'];
    
    // 1. Reset semua jadi 0
    $koneksi->query("UPDATE cabang SET is_pusat = 0");
    
    // 2. Set yang dipilih jadi 1
    $stmt = $koneksi->prepare("UPDATE cabang SET is_pusat = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Sukses', 'text'=>'Cabang Utama berhasil diubah'];
    }
    header("Location: cabang"); exit;
}

// --- LOGIKA: HAPUS CABANG ---
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cek apakah ini pusat?
    $cek = $koneksi->query("SELECT is_pusat FROM cabang WHERE id = '$id'")->fetch_assoc();
    
    if($cek['is_pusat'] == 1) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Ditolak', 'text'=>'Cabang Utama tidak boleh dihapus! Pindahkan dulu status pusatnya.'];
    } else {
        $koneksi->query("DELETE FROM cabang WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Cabang berhasil dihapus'];
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
                    <?php while($row = $data->fetch_assoc()): ?>
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
                                <a href="cabang?set_pusat=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Jadikan cabang ini sebagai PUSAT UTAMA?')">
                                    Set Utama
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="text-end px-4">
                            <button class="btn btn-sm btn-info text-white me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEdit<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <?php if(!$row['is_pusat']): ?>
                            <a href="cabang?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus cabang ini? Data terkait mungkin ikut terhapus.')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content bg-white">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Edit Cabang</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id_cabang" value="<?= $row['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Cabang</label>
                                        <input type="text" name="nama_cabang" class="form-control" value="<?= htmlspecialchars($row['nama_cabang']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Alamat Lengkap</label>
                                        <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($row['alamat']) ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_cabang" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Cabang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Cabang</label>
                    <input type="text" name="nama_cabang" class="form-control" placeholder="Contoh: Cabang Surabaya Barat" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Jl. Raya..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_cabang" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>