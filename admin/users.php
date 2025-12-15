<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') { header("Location: ../auth/login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen User";
$active_menu = "users";

// --- TAMBAH USER ---
if(isset($_POST['tambah_user'])) {
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $email = $koneksi->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $level = $_POST['level'];
    $cabang = ($_POST['cabang_id'] == 'NULL') ? NULL : $_POST['cabang_id'];
    
    $cek = $koneksi->query("SELECT id FROM users WHERE email = '$email'");
    if($cek->num_rows > 0) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Email sudah terdaftar!'];
    } else {
        $stmt = $koneksi->prepare("INSERT INTO users (nama, email, password, level, cabang_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $email, $password, $level, $cabang);
        
        if($stmt->execute()) {
            $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'User baru ditambahkan'];
        } else {
            $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Error', 'text'=>$koneksi->error];
        }
    }
    header("Location: users"); exit;
}

// --- EDIT USER ---
if(isset($_POST['edit_user'])) {
    $id = $_POST['id_user'];
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $email = $koneksi->real_escape_string($_POST['email']);
    $level = $_POST['level'];
    $cabang = ($_POST['cabang_id'] == 'NULL') ? NULL : $_POST['cabang_id'];
    
    if(!empty($_POST['password'])) {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE users SET nama=?, email=?, level=?, cabang_id=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $nama, $email, $level, $cabang, $pass, $id);
    } else {
        $stmt = $koneksi->prepare("UPDATE users SET nama=?, email=?, level=?, cabang_id=? WHERE id=?");
        $stmt->bind_param("ssssi", $nama, $email, $level, $cabang, $id);
    }
    
    if($stmt->execute()) {
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Data user diperbarui'];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Error', 'text'=>$koneksi->error];
    }
    header("Location: users"); exit;
}

// --- HAPUS USER ---
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    if($id == $_SESSION['user_id']) {
        $_SESSION['swal'] = ['icon'=>'warning', 'title'=>'Ditolak', 'text'=>'Tidak bisa menghapus diri sendiri!'];
    } else {
        $koneksi->query("DELETE FROM users WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'User berhasil dihapus'];
    }
    header("Location: users"); exit;
}

// Query Data
$sql = "SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.id ORDER BY u.id DESC";
$data = $koneksi->query($sql);

// List Cabang
$cabangs = $koneksi->query("SELECT * FROM cabang");
$list_cabang = []; while($c = $cabangs->fetch_assoc()) $list_cabang[] = $c;

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-users-cog me-2"></i>Manajemen User</h4>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-user-plus me-2"></i> Tambah User
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Nama User</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Cabang</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($data->num_rows > 0): ?>
                        <?php while($row = $data->fetch_assoc()): 
                            $jsonUser = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="px-4 fw-bold">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-secondary"></i>
                                    </div>
                                    <?= htmlspecialchars($row['nama']) ?>
                                </div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <?php 
                                    $badge = ($row['level']=='admin') ? 'bg-danger' : (($row['level']=='karyawan') ? 'bg-primary' : 'bg-success');
                                    echo "<span class='badge $badge rounded-pill'>".ucfirst($row['level'])."</span>";
                                ?>
                            </td>
                            <td>
                                <?= $row['nama_cabang'] ? '<span class="badge bg-info text-dark">'.$row['nama_cabang'].'</span>' : '<span class="badge bg-secondary">Global</span>' ?>
                            </td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick='openEditModal(<?= $jsonUser ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($row['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>, '<?= $row['nama'] ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada user.</td></tr>
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
                <h5 class="modal-title fw-bold">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select">
                            <option value="karyawan">Karyawan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Cabang</label>
                        <select name="cabang_id" class="form-select">
                            <option value="NULL">-- Global --</option>
                            <?php foreach($list_cabang as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option><?php endforeach; ?>
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

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_user" id="edit_id">
                <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" id="edit_nama" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Password Baru <small class="text-muted">(Kosongkan jika tidak ubah)</small></label><input type="password" name="password" class="form-control" placeholder="******"></div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label">Level</label>
                        <select name="level" id="edit_level" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="karyawan">Karyawan</option>
                            <option value="pelanggan">Pelanggan</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Cabang</label>
                        <select name="cabang_id" id="edit_cabang_id" class="form-select">
                            <option value="NULL">-- Global --</option>
                            <?php foreach($list_cabang as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_user" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_nama').value = user.nama;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_level').value = user.level;
    document.getElementById('edit_cabang_id').value = user.cabang_id ? user.cabang_id : "NULL";
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus ' + nama + '?', text: "Akun tidak bisa dikembalikan!", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
    }).then((r) => { if (r.isConfirmed) window.location.href = "?hapus=" + id; })
}
</script>

<?php include '../layouts/admin/footer.php'; ?>