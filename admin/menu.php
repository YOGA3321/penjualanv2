<?php
session_start();
// Aktifkan error reporting untuk debugging fatal error
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Menu";
$active_menu = "menu";

// --- LOGIKA KONTEKS (Admin vs Karyawan) ---
$level = $_SESSION['level'];
$session_cabang = $_SESSION['cabang_id'] ?? 0;
$view_cabang_id = 'pusat'; // Default Pusat

if ($level == 'admin') {
    // Admin ikut filter header
    $view_cabang_id = $_SESSION['view_cabang_id'] ?? 'pusat';
} else {
    // Karyawan ikut cabang sendiri
    $view_cabang_id = $session_cabang;
}

$is_global = ($view_cabang_id == 'pusat');

// --- CRUD: TAMBAH MENU ---
if(isset($_POST['tambah_menu'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_menu']);
    $kategori = $_POST['kategori_id'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $koneksi->real_escape_string($_POST['deskripsi']);
    // Handle string "NULL" dari select option
    $cabang = ($_POST['cabang_id'] === 'NULL' || $_POST['cabang_id'] === '') ? NULL : $_POST['cabang_id'];
    
    $gambar = NULL;
    if(!empty($_FILES['gambar']['name'])) {
        $target_dir = "../assets/images/menu/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $filename);
        $gambar = "assets/images/menu/" . $filename;
    }

    $stmt = $koneksi->prepare("INSERT INTO menu (nama_menu, kategori_id, harga, stok, deskripsi, cabang_id, gambar, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("siiisis", $nama, $kategori, $harga, $stok, $deskripsi, $cabang, $gambar);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Menu ditambahkan'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    header("Location: menu"); exit;
}

// --- CRUD: EDIT MENU (Fix ArgumentCountError) ---
if(isset($_POST['edit_menu'])) {
    $id = $_POST['edit_id'];
    $nama = $koneksi->real_escape_string($_POST['edit_nama']);
    $kategori = $_POST['edit_kategori_id'];
    $harga = $_POST['edit_harga'];
    $stok = $_POST['edit_stok'];
    $deskripsi = $koneksi->real_escape_string($_POST['edit_deskripsi']);
    $cabang = ($_POST['edit_cabang_id'] === 'NULL' || $_POST['edit_cabang_id'] === '') ? NULL : $_POST['edit_cabang_id'];
    $is_active = isset($_POST['edit_is_active']) ? 1 : 0;

    $q_old = $koneksi->query("SELECT gambar FROM menu WHERE id='$id'")->fetch_assoc();
    $gambar = $q_old['gambar']; 

    if(!empty($_FILES['edit_gambar']['name'])) {
        $target_dir = "../assets/images/menu/";
        $ext = pathinfo($_FILES['edit_gambar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['edit_gambar']['tmp_name'], $target_dir . $filename);
        $gambar = "assets/images/menu/" . $filename;
        if ($q_old['gambar'] && file_exists("../" . $q_old['gambar'])) unlink("../" . $q_old['gambar']);
    }

    $stmt = $koneksi->prepare("UPDATE menu SET nama_menu=?, kategori_id=?, harga=?, stok=?, deskripsi=?, cabang_id=?, gambar=?, is_active=? WHERE id=?");
    // [FIX] Parameter type string: "siiisisii" (9 tipe untuk 9 variabel)
    $stmt->bind_param("siiisisii", $nama, $kategori, $harga, $stok, $deskripsi, $cabang, $gambar, $is_active, $id);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Menu diperbarui'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Error', 'text'=>$koneksi->error];
    header("Location: menu"); exit;
}

// --- CRUD: HAPUS ---
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $koneksi->query("DELETE FROM menu WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Menu dihapus permanen'];
    } catch (mysqli_sql_exception $e) {
        // Jika error foreign key (1451), lakukan soft delete
        if ($e->getCode() == 1451) {
            $koneksi->query("UPDATE menu SET is_active = 0 WHERE id = '$id'");
            $_SESSION['swal'] = ['icon'=>'warning', 'title'=>'Diarsipkan', 'text'=>'Menu ini sudah ada transaksi, jadi hanya dinonaktifkan.'];
        } else {
            $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$e->getMessage()];
        }
    }
    header("Location: menu"); exit;
}

// --- QUERY LIST DATA ---
$sql = "SELECT m.*, k.nama_kategori, c.nama_cabang 
        FROM menu m 
        LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
        LEFT JOIN cabang c ON m.cabang_id = c.id";

if (!$is_global) {
    $sql .= " WHERE (m.cabang_id = '$view_cabang_id' OR m.cabang_id IS NULL)";
}
$sql .= " ORDER BY c.id ASC, m.nama_menu ASC";

$data = $koneksi->query($sql);

// --- DATA PENDUKUNG (DROPDOWN) ---

// 1. Kategori (Filter diperketat)
$sql_kat = "SELECT * FROM kategori_menu";
if (!$is_global) {
    // Jika sedang di Jogja, HANYA ambil (Kategori Global) ATAU (Kategori milik Jogja)
    $sql_kat .= " WHERE (cabang_id IS NULL OR cabang_id = '$view_cabang_id')";
}
$sql_kat .= " ORDER BY cabang_id ASC, nama_kategori ASC";
$q_kat = $koneksi->query($sql_kat);
$list_kat = []; while($k = $q_kat->fetch_assoc()) $list_kat[] = $k;

// 2. Cabang
$sql_cab = "SELECT * FROM cabang";
if (!$is_global) {
    $sql_cab .= " WHERE id = '$view_cabang_id'";
}
$q_cab = $koneksi->query($sql_cab);
$list_cabang = []; while($c = $q_cab->fetch_assoc()) $list_cabang[] = $c;

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-utensils me-2"></i>Manajemen Menu</h4>
        <small class="text-muted">
            Mode: <strong><?= $is_global ? 'Semua Cabang' : 'Cabang Terpilih' ?></strong>
        </small>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Tambah Menu
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Gambar</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="menu-container">
                    <?php if($data->num_rows > 0): ?>
                        <?php while($row = $data->fetch_assoc()): 
                            $jsonMenu = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="<?= $row['is_active'] ? '' : 'bg-light text-muted' ?>">
                            <td class="px-4">
                                <?php if($row['gambar']): ?>
                                    <img src="../<?= $row['gambar'] ?>" class="rounded" width="50" height="50" style="object-fit: cover; <?= $row['is_active'] ? '' : 'filter:grayscale(100%)' ?>">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width:50px; height:50px;"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= $row['nama_menu'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $row['nama_kategori'] ?></span></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td><span class="fw-bold <?= $row['stok']<=5 ? 'text-danger':'text-success' ?>"><?= $row['stok'] ?></span></td>
                            <td>
                                <?php if($row['cabang_id']): ?>
                                    <span class="badge bg-info text-dark"><?= $row['nama_cabang'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">GLOBAL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['is_active']): ?>
                                    <span class="badge bg-success rounded-pill">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill">Non-Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick='openEditModal(<?= $jsonMenu ?>)'><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>, '<?= $row['nama_menu'] ?>')"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data menu.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content bg-white" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Menu Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" name="nama_menu" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <?php foreach($list_kat as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hanya menampilkan kategori relevan.</small>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Harga (Rp)</label><input type="number" name="harga" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Stok Awal</label><input type="number" name="stok" class="form-control" value="100" required></div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lokasi Cabang</label>
                        <?php if ($is_global): ?>
                            <select name="cabang_id" class="form-select" required>
                                <option value="" selected disabled>-- Pilih Cabang --</option>
                                <?php foreach($list_cabang as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?php 
                                $nama_cabang_aktif = ""; 
                                foreach($list_cabang as $c) { if($c['id'] == $view_cabang_id) { $nama_cabang_aktif = $c['nama_cabang']; break; } } 
                            ?>
                            <input type="text" class="form-control bg-light" value="<?= $nama_cabang_aktif ?>" readonly>
                            <input type="hidden" name="cabang_id" value="<?= $view_cabang_id ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6 mb-3"><label class="form-label">Gambar</label><input type="file" name="gambar" class="form-control" accept="image/*"></div>
                    <div class="col-12 mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah_menu" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editMenuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content bg-white" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nama Menu</label><input type="text" name="edit_nama" id="edit_nama" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="edit_kategori_id" id="edit_kategori_id" class="form-select" required>
                            <?php foreach($list_kat as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Harga</label><input type="number" name="edit_harga" id="edit_harga" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Stok</label><input type="number" name="edit_stok" id="edit_stok" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lokasi</label>
                        <select name="edit_cabang_id" id="edit_cabang_id" class="form-select">
                            <option value="NULL">-- Global --</option>
                            <?php 
                            // Tampilkan semua cabang di Edit agar bisa dipindahkan
                            // Tapi untuk tambah, kita batasi
                            // Kita ambil full list cabang lagi khusus edit
                            $all_cabang = $koneksi->query("SELECT * FROM cabang");
                            while($ac = $all_cabang->fetch_assoc()): 
                            ?>
                                <option value="<?= $ac['id'] ?>"><?= $ac['nama_cabang'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ganti Foto</label><input type="file" name="edit_gambar" class="form-control"></div>
                    <div class="col-12 mb-3"><label class="form-label">Deskripsi</label><textarea name="edit_deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea></div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="edit_is_active" id="edit_is_active"><label class="form-check-label">Menu Aktif</label></div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_menu" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<script>
function openEditModal(menu) {
    document.getElementById('edit_id').value = menu.id;
    document.getElementById('edit_nama').value = menu.nama_menu;
    document.getElementById('edit_kategori_id').value = menu.kategori_id;
    document.getElementById('edit_harga').value = menu.harga;
    document.getElementById('edit_stok').value = menu.stok;
    document.getElementById('edit_deskripsi').value = menu.deskripsi;
    
    // Handle Null Cabang
    let cabVal = menu.cabang_id ? menu.cabang_id : 'NULL';
    document.getElementById('edit_cabang_id').value = cabVal;

    document.getElementById('edit_is_active').checked = (menu.is_active == 1);
    new bootstrap.Modal(document.getElementById('editMenuModal')).show();
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus ' + nama + '?', text: "Menu akan dinonaktifkan jika sudah pernah terjual.", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
    }).then((r) => { if (r.isConfirmed) window.location.href = "?hapus=" + id; })
}
</script>

<?php include '../layouts/admin/footer.php'; ?>