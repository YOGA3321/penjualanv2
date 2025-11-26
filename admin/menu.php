<?php
// admin/menu.php
require_once '../auth/koneksi.php';

$page_title = "Manajemen Menu";
$active_menu = "menu";

// --- 1. HANDLE INPUT KATEGORI JIKA KOSONG ---
// Kita cek apakah ada kategori, kalau tidak ada, buat dummy agar tidak error saat insert menu
$cek_kat = $koneksi->query("SELECT COUNT(*) as total FROM kategori_menu")->fetch_assoc();
if ($cek_kat['total'] == 0) {
    $koneksi->query("INSERT INTO kategori_menu (nama_kategori) VALUES ('Makanan Berat'), ('Minuman'), ('Cemilan')");
}

// --- 2. LOGIKA TAMBAH MENU ---
if (isset($_POST['tambah_menu'])) {
    $nama = $_POST['nama_menu'];
    $kat  = $_POST['kategori_id'];
    $harga= $_POST['harga'];
    $stok = $_POST['stok'];
    $desc = $_POST['deskripsi'];
    
    // Upload Gambar
    $gambarName = null;
    if (!empty($_FILES['gambar']['name'])) {
        $target_dir = "../assets/images/menu/";
        // Buat folder jika belum ada
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $newName = uniqid() . "." . $ext; // Nama file unik
        $target_file = $target_dir . $newName;
        
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambarName = "assets/images/menu/" . $newName; // Simpan path relatif
        }
    }

    $stmt = $koneksi->prepare("INSERT INTO menu (kategori_id, nama_menu, deskripsi, harga, stok, gambar) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiis", $kat, $nama, $desc, $harga, $stok, $gambarName);
    
    if ($stmt->execute()) $sukses = "Menu berhasil ditambahkan!";
    else $error = "Gagal: " . $koneksi->error;
}

// --- 3. LOGIKA HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus file gambar lama jika ada
    $old = $koneksi->query("SELECT gambar FROM menu WHERE id='$id'")->fetch_assoc();
    if ($old['gambar'] && file_exists("../" . $old['gambar'])) {
        unlink("../" . $old['gambar']);
    }
    
    $koneksi->query("DELETE FROM menu WHERE id='$id'");
    header("Location: menu");
    exit;
}

// AMBIL DATA
$menus = $koneksi->query("SELECT menu.*, kategori_menu.nama_kategori 
                          FROM menu 
                          LEFT JOIN kategori_menu ON menu.kategori_id = kategori_menu.id 
                          ORDER BY menu.id DESC");
$kategoris = $koneksi->query("SELECT * FROM kategori_menu");

$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#menuModal">
    <i class="fas fa-plus me-2"></i>Tambah Menu
</button>';

include '../layouts/admin/header.php';
?>

<?php if(isset($sukses)): ?><div class="alert alert-success"><?= $sukses ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($m = $menus->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if($m['gambar']): ?>
                                <img src="../<?= $m['gambar'] ?>" class="rounded" width="50" height="50" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px; height:50px;"><i class="fas fa-image text-muted"></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= $m['nama_menu'] ?></td>
                        <td><span class="badge bg-secondary"><?= $m['nama_kategori'] ?></span></td>
                        <td>Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge <?= $m['stok'] > 5 ? 'bg-success' : 'bg-warning' ?>">
                                <?= $m['stok'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="?hapus=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus menu ini?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Tambah Menu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama Menu</label><input type="text" name="nama_menu" class="form-control" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Harga (Rp)</label><input type="number" name="harga" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Stok Awal</label><input type="number" name="stok" class="form-control" value="100" required></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <?php 
                            $kategoris->data_seek(0);
                            while($k = $kategoris->fetch_assoc()): 
                            ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Foto Menu</label><input type="file" name="gambar" class="form-control" accept="image/*"></div>
                    <div class="mb-3"><label class="form-label">Deskripsi Singkat</label><textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_menu" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>