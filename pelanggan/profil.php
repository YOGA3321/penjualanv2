<?php
session_start();
// Cek Login Pelanggan
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'pelanggan') { 
    header("Location: ../login"); exit; 
}
require_once '../auth/koneksi.php';

$uid = $_SESSION['user_id'];

// PROSES UPDATE
if(isset($_POST['simpan_profil'])) {
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $hp = $koneksi->real_escape_string($_POST['no_hp']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    
    $sql = "UPDATE users SET nama = '$nama', no_hp = '$hp', alamat = '$alamat' WHERE id = '$uid'";
    
    if($koneksi->query($sql)) {
        $_SESSION['nama'] = $nama;
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Profil Anda diperbarui!'];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Terjadi kesalahan sistem.'];
    }
    header("Location: profil"); exit; // Redirect tanpa .php
}

// Ambil Data Terbaru
$u = $koneksi->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .card-form { border-radius: 20px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 500px;">
    <a href="index" class="text-decoration-none text-muted mb-3 d-block fw-bold">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
    </a>
    
    <div class="card card-form p-4 bg-white">
        <h4 class="fw-bold text-primary mb-4 text-center">Profil Saya</h4>
        
        <form method="POST">
            <div class="text-center mb-4">
                <?php if(!empty($u['foto'])): ?>
                    <img src="<?= $u['foto'] ?>" class="profile-img">
                <?php else: ?>
                    <div class="profile-img bg-light d-flex align-items-center justify-content-center mx-auto">
                        <i class="fas fa-user fa-3x text-secondary"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Email (Terhubung Google)</label>
                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($u['email'] ?? '') ?>" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($u['nama'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">No. HP / WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($u['no_hp'] ?? '') ?>" placeholder="08...">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-muted">Alamat</label>
                <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat pengiriman..."><?= htmlspecialchars($u['alamat'] ?? '') ?></textarea>
            </div>

            <button type="submit" name="simpan_profil" class="btn btn-primary w-100 rounded-pill fw-bold py-2">
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['swal'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['swal']['icon'] ?>',
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        timer: 2000, showConfirmButton: false
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>

</body>
</html>