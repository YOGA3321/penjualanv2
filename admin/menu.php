<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Menu";
$active_menu = "menu";

// Context
$view_cabang = $_SESSION['view_cabang_id'] ?? 'pusat'; 
$is_global = ($view_cabang == 'pusat');

// --- TAMBAH MENU ---
if(isset($_POST['tambah_menu'])) {
    $nama = $koneksi->real_escape_string($_POST['nama_menu']);
    $kategori = $_POST['kategori_id'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $koneksi->real_escape_string($_POST['deskripsi']);
    $cabang = ($_POST['cabang_id'] === 'NULL') ? NULL : $_POST['cabang_id'];
    
    // Promo Logic
    $is_promo = isset($_POST['is_promo']) ? 1 : 0;
    $harga_promo = $_POST['harga_promo'] ?? 0;
    if($is_promo == 0) $harga_promo = 0;

    $gambar = NULL;
    if(!empty($_FILES['gambar']['name'])) {
        $target_dir = "../assets/images/menu/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $filename);
        $gambar = "assets/images/menu/" . $filename;
    }

    $stmt = $koneksi->prepare("INSERT INTO menu (nama_menu, kategori_id, harga, stok, deskripsi, cabang_id, gambar, is_active, is_promo, harga_promo) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
    $stmt->bind_param("siiisisid", $nama, $kategori, $harga, $stok, $deskripsi, $cabang, $gambar, $is_promo, $harga_promo);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Menu ditambahkan'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$koneksi->error];
    header("Location: menu"); exit;
}

// --- EDIT MENU (FIXED ERROR 't') ---
if(isset($_POST['edit_menu'])) {
    $id = $_POST['edit_id'];
    $nama = $koneksi->real_escape_string($_POST['edit_nama']);
    $kategori = $_POST['edit_kategori_id'];
    $harga = $_POST['edit_harga'];
    $stok = $_POST['edit_stok'];
    $deskripsi = $koneksi->real_escape_string($_POST['edit_deskripsi']);
    $cabang = ($_POST['edit_cabang_id'] === 'NULL') ? NULL : $_POST['edit_cabang_id'];
    $is_active = isset($_POST['edit_is_active']) ? 1 : 0;
    $is_promo = isset($_POST['edit_is_promo']) ? 1 : 0;
    $harga_promo = $_POST['edit_harga_promo'] ?? 0;
    if($is_promo == 0) $harga_promo = 0;

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

    $stmt = $koneksi->prepare("UPDATE menu SET nama_menu=?, kategori_id=?, harga=?, stok=?, deskripsi=?, cabang_id=?, gambar=?, is_active=?, is_promo=?, harga_promo=? WHERE id=?");
    
    // [FIX] Hapus huruf 't', ganti dengan urutan yang benar: s,i,i,i,s,i,s,i,i,d,i
    $stmt->bind_param("siiisisiddi", $nama, $kategori, $harga, $stok, $deskripsi, $cabang, $gambar, $is_active, $is_promo, $harga_promo, $id);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Menu diperbarui'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Error', 'text'=>$koneksi->error];
    header("Location: menu"); exit;
}

// ... (Sisa kode Hapus dan HTML Tampilan Menu tetap sama seperti sebelumnya) ...
// Silakan copy bagian bawah dari kode admin/menu.php sebelumnya, 
// yang penting bagian 'bind_param' di atas sudah diperbaiki ("siiisisiddi").

// --- BIAR GAMPANG, SAYA TULIS ULANG BAGIAN BAWAHNYA DISINI ---

if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $koneksi->query("DELETE FROM menu WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Menu dihapus permanen'];
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) { 
            $koneksi->query("UPDATE menu SET is_active = 0 WHERE id = '$id'");
            $_SESSION['swal'] = ['icon'=>'warning', 'title'=>'Diarsipkan', 'text'=>'Menu dinonaktifkan karena ada riwayat transaksi.'];
        } else {
            $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>$e->getMessage()];
        }
    }
    header("Location: menu"); exit;
}

$where = "";
if ($_SESSION['level'] == 'admin' && !$is_global) {
    $where = "WHERE (m.cabang_id = '$view_cabang' OR m.cabang_id IS NULL)";
} elseif ($_SESSION['level'] != 'admin') {
    $cabang_user = $_SESSION['cabang_id'];
    $where = "WHERE (m.cabang_id = '$cabang_user' OR m.cabang_id IS NULL)";
}

$sql = "SELECT m.*, k.nama_kategori, c.nama_cabang 
        FROM menu m 
        LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
        LEFT JOIN cabang c ON m.cabang_id = c.id 
        $where 
        ORDER BY c.id ASC, m.nama_menu ASC";
$data = $koneksi->query($sql);

$q_kat = $koneksi->query("SELECT * FROM kategori_menu ORDER BY nama_kategori ASC");
$list_kat = []; while($k = $q_kat->fetch_assoc()) $list_kat[] = $k;
$q_cab = $koneksi->query("SELECT * FROM cabang");
$list_cabang = []; while($c = $q_cab->fetch_assoc()) $list_cabang[] = $c;

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-utensils me-2"></i>Manajemen Menu</h4>
        <small class="text-muted">Mode: <strong><?= $is_global ? 'Semua Cabang' : 'Cabang Terpilih' ?></strong></small>
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
                        <th>Harga</th>
                        <th>Promo</th>
                        <th>Stok</th>
                        <th>Lokasi</th>
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
                                    <img src="../<?= $row['gambar'] ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width:50px; height:50px;"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= $row['nama_menu'] ?></td>
                            <td>
                                <?php if($row['is_promo']): ?>
                                    <span class="text-decoration-line-through text-muted small">Rp <?= number_format($row['harga']) ?></span><br>
                                    <span class="text-danger fw-bold">Rp <?= number_format($row['harga_promo']) ?></span>
                                <?php else: ?>
                                    Rp <?= number_format($row['harga']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['is_promo'] ? '<span class="badge bg-danger">PROMO</span>' : '-' ?></td>
                            <td><span class="fw-bold <?= $row['stok']<=5 ? 'text-danger':'text-success' ?>"><?= $row['stok'] ?></span></td>
                            <td><?= $row['nama_cabang'] ? $row['nama_cabang'] : 'GLOBAL' ?></td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick='openEditModal(<?= $jsonMenu ?>)'><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>, '<?= $row['nama_menu'] ?>')"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content bg-white" enctype="multipart/form-data">
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Menu</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label>Nama</label><input type="text" name="nama_menu" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label>Kategori</label><select name="kategori_id" class="form-select" required><?php foreach($list_kat as $k): ?><option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 mb-3"><label>Harga</label><input type="number" name="harga" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Stok</label><input type="number" name="stok" class="form-control" value="100" required></div>
                    <div class="col-md-4 mb-3"><label>Lokasi</label><select name="cabang_id" class="form-select"><option value="NULL">Global</option><?php foreach($list_cabang as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option><?php endforeach; ?></select></div>
                    
                    <div class="col-12 mb-3 bg-light p-3 rounded">
                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_promo" id="add_is_promo" onchange="togglePromo('add')"><label class="form-check-label fw-bold text-danger">Aktifkan Promo?</label></div>
                        <div class="mb-0" id="add_promo_container" style="display:none;"><label class="small">Harga Diskon</label><input type="number" name="harga_promo" class="form-control border-danger"></div>
                    </div>

                    <div class="col-md-6 mb-3"><label>Gambar</label><input type="file" name="gambar" class="form-control"></div>
                    <div class="col-12"><label>Deskripsi</label><textarea name="deskripsi" class="form-control"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah_menu" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editMenuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content bg-white" enctype="multipart/form-data">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Menu</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="row">
                    <div class="col-md-6 mb-3"><label>Nama</label><input type="text" name="edit_nama" id="edit_nama" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label>Kategori</label><select name="edit_kategori_id" id="edit_kategori_id" class="form-select" required><?php foreach($list_kat as $k): ?><option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 mb-3"><label>Harga Normal</label><input type="number" name="edit_harga" id="edit_harga" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Stok</label><input type="number" name="edit_stok" id="edit_stok" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Lokasi</label><select name="edit_cabang_id" id="edit_cabang_id" class="form-select"><option value="NULL">Global</option><?php foreach($list_cabang as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option><?php endforeach; ?></select></div>
                    
                    <div class="col-12 mb-3 bg-light p-3 rounded">
                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="edit_is_promo" id="edit_is_promo" onchange="togglePromo('edit')"><label class="form-check-label fw-bold text-danger">Lagi Promo?</label></div>
                        <div class="mb-0" id="edit_promo_container" style="display:none;"><label class="small">Harga Diskon</label><input type="number" name="edit_harga_promo" id="edit_harga_promo" class="form-control border-danger"></div>
                    </div>

                    <div class="col-md-6 mb-3"><label>Ganti Gambar</label><input type="file" name="edit_gambar" class="form-control"></div>
                    <div class="col-12"><label>Deskripsi</label><textarea name="edit_deskripsi" id="edit_deskripsi" class="form-control"></textarea></div>
                    <div class="col-12 pt-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="edit_is_active" id="edit_is_active"><label class="form-check-label">Menu Aktif</label></div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_menu" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<script>
function togglePromo(type) {
    const check = document.getElementById(type + '_is_promo');
    const container = document.getElementById(type + '_promo_container');
    container.style.display = check.checked ? 'block' : 'none';
}
function openEditModal(menu) {
    document.getElementById('edit_id').value = menu.id;
    document.getElementById('edit_nama').value = menu.nama_menu;
    document.getElementById('edit_kategori_id').value = menu.kategori_id;
    document.getElementById('edit_harga').value = menu.harga;
    document.getElementById('edit_stok').value = menu.stok;
    document.getElementById('edit_deskripsi').value = menu.deskripsi;
    document.getElementById('edit_cabang_id').value = menu.cabang_id ? menu.cabang_id : "NULL";
    document.getElementById('edit_is_active').checked = (menu.is_active == 1);
    
    // Promo
    const isPromo = (menu.is_promo == 1);
    document.getElementById('edit_is_promo').checked = isPromo;
    document.getElementById('edit_harga_promo').value = menu.harga_promo;
    togglePromo('edit');
    
    new bootstrap.Modal(document.getElementById('editMenuModal')).show();
}
function confirmDelete(id, nama) {
    Swal.fire({ title: 'Hapus '+nama+'?', text: "Data akan dihapus.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya', confirmButtonColor: '#d33'}).then((r) => { if(r.isConfirmed) window.location.href="?hapus="+id; });
}
</script>
<?php include '../layouts/admin/footer.php'; ?>