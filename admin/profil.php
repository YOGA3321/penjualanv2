<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Pengaturan Akun";
$active_menu = "profil";

$uid = $_SESSION['user_id'];

// PROSES UPDATE
if(isset($_POST['update_profil'])) {
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $hp = $koneksi->real_escape_string($_POST['no_hp']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    
    $sql = "UPDATE users SET nama = '$nama', no_hp = '$hp', alamat = '$alamat' WHERE id = '$uid'";
    
    if($koneksi->query($sql)) {
        $_SESSION['nama'] = $nama; 
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Profil diperbarui'];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    }
    // Redirect Tanpa .php
    header("Location: profil"); exit;
}

$u = $koneksi->query("SELECT * FROM users WHERE id = '$uid'")->fetch_assoc();

include '../layouts/admin/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5">
                <h4 class="fw-bold text-primary mb-4 text-center">Edit Profil Saya</h4>
                
                <form method="POST">
                    <div class="text-center mb-4">
                        <?php if(!empty($u['foto'])): ?>
                            <img src="<?= $u['foto'] ?>" class="rounded-circle border p-1 shadow-sm" width="100" height="100" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width:100px; height:100px;">
                                <i class="fas fa-user fa-3x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border"><?= ucfirst($u['level'] ?? 'User') ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Email</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($u['email'] ?? '') ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control fw-bold" value="<?= htmlspecialchars($u['nama'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">No. HP / WhatsApp</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($u['no_hp'] ?? '') ?>" placeholder="08...">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($u['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="update_profil" class="btn btn-primary fw-bold py-2 rounded-pill">
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>