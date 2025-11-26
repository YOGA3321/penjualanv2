<?php
session_start();
require_once '../auth/koneksi.php';

// 1. CEK AKSES: HANYA ADMIN
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: index"); // Lempar ke index (yang akan routing ke laporan/login)
    exit;
}

$page_title = "Manajemen User";
$active_menu = "users";

// --- LOGIKA TAMBAH USER ---
if (isset($_POST['tambah_user'])) {
    $nama  = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role  = $_POST['level'];
    $hp    = $_POST['no_hp'];
    
    // Logika Cabang
    $cabang_id = ($role == 'admin') ? NULL : $_POST['cabang_id'];

    // Cek Email Duplikat
    $cek = $koneksi->query("SELECT id FROM users WHERE email = '$email'");
    if ($cek->num_rows > 0) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Gagal!',
            'text' => 'Email sudah digunakan user lain.'
        ];
    } else {
        $stmt = $koneksi->prepare("INSERT INTO users (nama, email, password, no_hp, level, cabang_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $nama, $email, $pass, $hp, $role, $cabang_id);
        
        if ($stmt->execute()) {
            $_SESSION['swal'] = [
                'icon' => 'success',
                'title' => 'Berhasil!',
                'text' => 'User baru telah ditambahkan.'
            ];
        } else {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => $koneksi->error
            ];
        }
    }
    // Refresh halaman agar form tidak submit ulang
    header("Location: users");
    exit;
}

// --- LOGIKA HAPUS USER ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // 2. CEGAH HAPUS DIRI SENDIRI
    if ($id == $_SESSION['user_id']) { 
        $_SESSION['swal'] = [
            'icon' => 'warning',
            'title' => 'Akses Ditolak',
            'text' => 'Anda tidak dapat menghapus akun Anda sendiri!'
        ];
    } else {
        $koneksi->query("DELETE FROM users WHERE id = '$id'");
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'Terhapus',
            'text' => 'Data user berhasil dihapus.'
        ];
    }
    header("Location: users");
    exit;
}

// AMBIL DATA
$query_users = "SELECT users.*, cabang.nama_cabang 
                FROM users 
                LEFT JOIN cabang ON users.cabang_id = cabang.id 
                ORDER BY level ASC, nama ASC";
$users = $koneksi->query($query_users);
$cabangs = $koneksi->query("SELECT * FROM cabang");

// Tombol Header
$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
    <i class="fas fa-plus me-2"></i>User Baru
</button>';

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama</th>
                        <th>Email</th>
                        <th>Cabang</th>
                        <th>Level</th>
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
                            <?php if($u['id'] == $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled title="Akun Anda"><i class="fas fa-trash"></i></button>
                            <?php else: ?>
                                <a href="javascript:void(0);" onclick="confirmDelete('<?= $u['id'] ?>')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
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
    function toggleCabang() {
        var level = document.getElementById('levelSelect').value;
        var wrapper = document.getElementById('cabangWrapper');
        wrapper.style.display = (level === 'admin') ? 'none' : 'block';
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus User?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?hapus=" + id;
            }
        })
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>

<?php if (isset($_SESSION['swal'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['swal']['icon'] ?>',
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        timer: 2500,
        showConfirmButton: false
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>