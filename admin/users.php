<?php
// admin/users.php
require_once '../auth/koneksi.php';

// --- CEK AKSES: HANYA ADMIN ---
session_start(); // Pastikan session start manual disini utk cek level sblm header
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    echo "<script>alert('Akses Ditolak! Halaman ini hanya untuk Admin Utama.'); window.location='laporan';</script>";
    exit;
}

$page_title = "Manajemen User";
$active_menu = "users";

// TAMBAH USER
if (isset($_POST['tambah_user'])) {
    $nama  = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role  = $_POST['level'];
    $hp    = $_POST['no_hp'];
    
    // Logika Cabang: Jika admin, cabang_id NULL. Jika karyawan, ambil dari input.
    $cabang_id = ($role == 'admin') ? NULL : $_POST['cabang_id'];

    $cek = $koneksi->query("SELECT id FROM users WHERE email = '$email'");
    if ($cek->num_rows > 0) {
        $error = "Email sudah digunakan!";
    } else {
        // Prepare statement agar aman
        $stmt = $koneksi->prepare("INSERT INTO users (nama, email, password, no_hp, level, cabang_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $nama, $email, $pass, $hp, $role, $cabang_id);
        
        if ($stmt->execute()) $sukses = "User berhasil ditambahkan.";
        else $error = "Error: " . $koneksi->error;
    }
}

// HAPUS USER
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    if ($id != $_SESSION['user_id']) { 
        $koneksi->query("DELETE FROM users WHERE id = '$id'");
        header("Location: users");
        exit;
    } else {
        $error = "Tidak bisa menghapus akun sendiri.";
    }
}

// AMBIL DATA USER + NAMA CABANGNYA
$query_users = "SELECT users.*, cabang.nama_cabang 
                FROM users 
                LEFT JOIN cabang ON users.cabang_id = cabang.id 
                ORDER BY level ASC, nama ASC";
$users = $koneksi->query($query_users);

// AMBIL DATA CABANG UTK DROPDOWN
$cabangs = $koneksi->query("SELECT * FROM cabang");

$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
    <i class="fas fa-plus me-2"></i>User Baru
</button>';

include '../layouts/admin/header.php';
?>

<?php if(isset($sukses)): ?><div class="alert alert-success"><?= $sukses ?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama</th>
                        <th>Email</th>
                        <th>Cabang</th> <th>Level</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                                    <i class="fas fa-user text-secondary"></i>
                                </div>
                                <div>
                                    <strong><?= $u['nama'] ?></strong><br>
                                    <small class="text-muted"><?= $u['no_hp'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= $u['email'] ?></td>
                        <td>
                            <?php if($u['level'] == 'admin'): ?>
                                <span class="badge bg-dark">Pusat</span>
                            <?php else: ?>
                                <?= $u['nama_cabang'] ?? '<span class="text-danger">-</span>' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $badge = $u['level'] == 'admin' ? 'bg-danger' : 'bg-info'; ?>
                            <span class="badge <?= $badge ?>"><?= ucfirst($u['level']) ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus user ini?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Tambah User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email (Username)</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">No HP</label><input type="text" name="no_hp" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Level</label>
                            <select name="level" class="form-select" id="levelSelect" onchange="toggleCabang()">
                                <option value="karyawan">Karyawan</option>
                                <option value="admin">Admin Pusat</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="cabangWrapper">
                            <label class="form-label">Penempatan Cabang</label>
                            <select name="cabang_id" class="form-select">
                                <?php 
                                $cabangs->data_seek(0);
                                while($c = $cabangs->fetch_assoc()): 
                                ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_user" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script sederhana untuk menyembunyikan pilihan cabang jika Admin yang dipilih
    function toggleCabang() {
        var level = document.getElementById('levelSelect').value;
        var wrapper = document.getElementById('cabangWrapper');
        if (level === 'admin') {
            wrapper.style.display = 'none';
        } else {
            wrapper.style.display = 'block';
        }
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>