<?php
session_start();
// 1. CEK LOGIN
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }

require_once '../auth/koneksi.php';

$page_title = "Manajemen Menu";
$active_menu = "menu";

$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';

if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}

// --- TAMBAH MENU ---
if (isset($_POST['tambah_menu'])) {
    $nama = htmlspecialchars($_POST['nama_menu']);
    $kat  = $_POST['kategori_id'];
    $harga= $_POST['harga'];
    $stok = $_POST['stok'];
    $desc = htmlspecialchars($_POST['deskripsi']);
    
    if ($harga < 0 || $stok < 0) {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Harga dan Stok tidak boleh minus!'];
    } else {
        $target_cabang = $cabang_id;
        if ($level == 'admin' && !empty($_POST['cabang_override'])) {
            $target_cabang = $_POST['cabang_override'];
        }
        if ($target_cabang == 0 || $target_cabang == '0') { $target_cabang = NULL; }

        $gambarName = null;
        if (!empty($_FILES['gambar']['name'])) {
            $target_dir = "../assets/images/menu/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $newName = uniqid() . "." . $ext;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $newName)) {
                $gambarName = "assets/images/menu/" . $newName;
            }
        }

        $stmt = $koneksi->prepare("INSERT INTO menu (cabang_id, kategori_id, nama_menu, deskripsi, harga, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiis", $target_cabang, $kat, $nama, $desc, $harga, $stok, $gambarName);
        
        if ($stmt->execute()) {
            $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Menu berhasil ditambahkan!'];
        } else {
            $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => $stmt->error];
        }
    }
    header("Location: menu"); exit;
}

// --- EDIT MENU ---
if (isset($_POST['edit_menu'])) {
    $id = $_POST['edit_id'];
    $nama = htmlspecialchars($_POST['edit_nama']);
    $kat  = $_POST['edit_kategori_id'];
    $harga= $_POST['edit_harga'];
    $stok = $_POST['edit_stok'];
    $desc = htmlspecialchars($_POST['edit_deskripsi']);

    if ($harga < 0 || $stok < 0) {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Harga dan Stok tidak boleh minus!'];
    } else {
        if (!empty($_FILES['edit_gambar']['name'])) {
            // Ganti Foto
            $old = $koneksi->query("SELECT gambar FROM menu WHERE id='$id'")->fetch_assoc();
            if ($old['gambar'] && file_exists("../" . $old['gambar'])) unlink("../" . $old['gambar']);

            $target_dir = "../assets/images/menu/";
            $ext = strtolower(pathinfo($_FILES['edit_gambar']['name'], PATHINFO_EXTENSION));
            $newName = uniqid() . "." . $ext;
            move_uploaded_file($_FILES['edit_gambar']['tmp_name'], $target_dir . $newName);
            $gambarName = "assets/images/menu/" . $newName;

            $stmt = $koneksi->prepare("UPDATE menu SET nama_menu=?, kategori_id=?, harga=?, stok=?, deskripsi=?, gambar=? WHERE id=?");
            $stmt->bind_param("siiissi", $nama, $kat, $harga, $stok, $desc, $gambarName, $id);
        } else {
            // Tanpa Ganti Foto
            $stmt = $koneksi->prepare("UPDATE menu SET nama_menu=?, kategori_id=?, harga=?, stok=?, deskripsi=? WHERE id=?");
            $stmt->bind_param("siiisi", $nama, $kat, $harga, $stok, $desc, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Menu diperbarui!'];
        } else {
            $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => $stmt->error];
        }
    }
    header("Location: menu"); exit;
}

// --- DELETE ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $old = $koneksi->query("SELECT gambar FROM menu WHERE id='$id'")->fetch_assoc();
    if ($old['gambar'] && file_exists("../" . $old['gambar'])) unlink("../" . $old['gambar']);
    $koneksi->query("DELETE FROM menu WHERE id='$id'");
    $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Terhapus', 'text' => 'Menu berhasil dihapus!'];
    header("Location: menu"); exit;
}

// --- DATA PENDUKUNG (DROPDOWN) ---
$sql_kat = "SELECT * FROM kategori_menu";
if ($level != 'admin' || isset($_SESSION['view_cabang_id'])) {
    $sql_kat .= " WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL)";
}
$kategoris = $koneksi->query($sql_kat);

if ($level == 'admin') { $list_cabang = $koneksi->query("SELECT * FROM cabang"); }

$header_action_btn = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#menuModal"><i class="fas fa-plus me-2"></i>Tambah Menu</button>';

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <?php if($level == 'admin'): ?><th>Cabang</th><?php endif; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="menu-container">
                    <tr><td colspan="7" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Memuat data menu...</p>
                    </td></tr>
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
                    <div class="mb-3"><label>Nama Menu</label><input type="text" name="nama_menu" class="form-control" required></div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="">Pilih Kategori...</option>
                            <?php $kategoris->data_seek(0); while($k = $kategoris->fetch_assoc()): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Harga</label><input type="number" name="harga" class="form-control" min="0" required></div>
                        <div class="col-6 mb-3"><label>Stok</label><input type="number" name="stok" class="form-control" min="0" required></div>
                    </div>
                    <div class="mb-3"><label>Foto</label><input type="file" name="gambar" class="form-control"></div>
                    <div class="mb-3"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
                    
                    <?php if($level == 'admin'): ?>
                    <div class="mb-3 bg-light p-3 rounded">
                        <label class="small text-muted fw-bold">Simpan ke Cabang (Opsional)</label>
                        <select name="cabang_override" class="form-select form-select-sm">
                            <option value="">Global / Pusat (Tampil di Semua Cabang)</option>
                            <?php if(isset($list_cabang)) { $list_cabang->data_seek(0); while($c = $list_cabang->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>" <?= ($cabang_id == $c['id']) ? 'selected' : '' ?>>
                                    <?= $c['nama_cabang'] ?>
                                </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_menu" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Edit Menu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3"><label>Nama Menu</label><input type="text" name="edit_nama" id="edit_nama" class="form-control" required></div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="edit_kategori_id" id="edit_kategori_id" class="form-select" required>
                            <option value="">Pilih Kategori...</option>
                            <?php $kategoris->data_seek(0); while($k = $kategoris->fetch_assoc()): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Harga</label><input type="number" name="edit_harga" id="edit_harga" class="form-control" min="0" required></div>
                        <div class="col-6 mb-3"><label>Stok</label><input type="number" name="edit_stok" id="edit_stok" class="form-control" min="0" required></div>
                    </div>
                    <div class="mb-3">
                        <label>Ganti Foto (Opsional)</label>
                        <input type="file" name="edit_gambar" class="form-control">
                    </div>
                    <div class="mb-3"><label>Deskripsi</label><textarea name="edit_deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" name="edit_menu" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    let menuEventSource = null;

    function startMenuUpdates() {
        if (menuEventSource) menuEventSource.close();

        // Deteksi cabang yang sedang dilihat (untuk dikirim ke API)
        const currentBranch = '<?= ($level=='admin' && !isset($_SESSION['view_cabang_id'])) ? 'pusat' : $cabang_id ?>';

        // Buka koneksi SSE
        menuEventSource = new EventSource(`api/sse_menu.php?view_cabang=${currentBranch}`);

        menuEventSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            
            if(result.status === 'success') {
                const container = document.getElementById('menu-container');
                let html = '';

                if(result.data.length === 0) {
                    html = `<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data menu.</td></tr>`;
                } else {
                    result.data.forEach(m => {
                        // Format Harga Rp
                        let hargaRp = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(m.harga);
                        
                        // Tampilan Foto
                        let imgHtml = m.gambar 
                            ? `<img src="../${m.gambar}" class="rounded" width="50" height="50" style="object-fit:cover;">`
                            : `<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width:50px; height:50px;"><i class="fas fa-image"></i></div>`;

                        // Tampilan Cabang (Khusus Admin)
                        let cabangHtml = '';
                        <?php if($level == 'admin'): ?>
                            let labelCabang = m.nama_cabang ? `<span class="badge bg-info">${m.nama_cabang}</span>` : `<span class="badge bg-secondary">Global</span>`;
                            cabangHtml = `<td>${labelCabang}</td>`;
                        <?php endif; ?>

                        // Buat Baris Tabel
                        html += `
                        <tr>
                            <td>${imgHtml}</td>
                            <td class="fw-bold">${m.nama_menu}</td>
                            <td><span class="badge bg-light text-dark border">${m.nama_kategori || '-'}</span></td>
                            <td>${hargaRp}</td>
                            <td><span class="badge ${m.stok > 5 ? 'bg-success' : 'bg-danger'}">${m.stok}</span></td>
                            ${cabangHtml}
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                    onclick='openEditModal(${JSON.stringify(m)})'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="javascript:void(0);" onclick="confirmDelete('${m.id}')" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>`;
                    });
                }
                container.innerHTML = html;
            }
        };
        
        menuEventSource.onerror = function() {
            // Handle error silent reconnect
        };
    }

    function openEditModal(menu) {
        document.getElementById('edit_id').value = menu.id;
        document.getElementById('edit_nama').value = menu.nama_menu;
        document.getElementById('edit_kategori_id').value = menu.kategori_id;
        document.getElementById('edit_harga').value = menu.harga;
        document.getElementById('edit_stok').value = menu.stok;
        document.getElementById('edit_deskripsi').value = menu.deskripsi;
        
        var myModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
        myModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Menu?', text: "Data tidak bisa dikembalikan!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = "?hapus=" + id; }
        })
    }

    document.addEventListener('DOMContentLoaded', () => {
        startMenuUpdates();
    });
</script>

<?php include '../layouts/admin/footer.php'; ?>