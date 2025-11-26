<?php
session_start();
// 1. CEK LOGIN (KEAMANAN)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

require_once '../auth/koneksi.php';

$page_title = "Kategori Menu";
$active_menu = "kategori";

$cabang_id = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';

if ($level == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}

// --- TAMBAH KATEGORI ---
if (isset($_POST['tambah'])) {
    $nama = htmlspecialchars($_POST['nama_kategori']);
    
    // Logika Target Cabang
    $target_cabang = $cabang_id;
    if ($level == 'admin' && !empty($_POST['cabang_override'])) {
        $target_cabang = $_POST['cabang_override'];
    }
    
    // Ubah 0 jadi NULL agar masuk sebagai Global
    if ($target_cabang == 0 || $target_cabang == '0') {
        $target_cabang = NULL;
    }

    $stmt = $koneksi->prepare("INSERT INTO kategori_menu (nama_kategori, cabang_id) VALUES (?, ?)");
    $stmt->bind_param("si", $nama, $target_cabang);

    if ($stmt->execute()) {
        $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Kategori berhasil disimpan.'];
    } else {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => $stmt->error];
    }
    header("Location: kategori"); 
    exit;
}

// --- EDIT KATEGORI ---
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = htmlspecialchars($_POST['nama_kategori']);
    
    $stmt = $koneksi->prepare("UPDATE kategori_menu SET nama_kategori=? WHERE id=?");
    $stmt->bind_param("si", $nama, $id);
    
    if ($stmt->execute()) {
        $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Kategori berhasil diupdate.'];
    } else {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => $stmt->error];
    }
    header("Location: kategori");
    exit;
}

// --- HAPUS KATEGORI ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM kategori_menu WHERE id='$id'");
    $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Terhapus', 'text' => 'Kategori berhasil dihapus.'];
    header("Location: kategori");
    exit;
}

// --- QUERY DATA (PEWARISAN) ---
$sql = "SELECT k.*, c.nama_cabang FROM kategori_menu k 
        LEFT JOIN cabang c ON k.cabang_id = c.id";

if ($level == 'admin' && !isset($_SESSION['view_cabang_id'])) {
    // Mode Global Admin: Lihat Semua
    $sql .= " WHERE 1=1"; 
} else {
    // Mode Cabang: Lihat Milik Cabang + Global
    $sql .= " WHERE (k.cabang_id = '$cabang_id' OR k.cabang_id IS NULL)";
}
$sql .= " ORDER BY k.cabang_id ASC, k.id DESC";

$data = $koneksi->query($sql);

// Ambil data cabang untuk dropdown admin
if ($level == 'admin') {
    $list_cabang = $koneksi->query("SELECT * FROM cabang");
}

$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="fas fa-plus me-2"></i>Tambah Kategori
</button>';

include '../layouts/admin/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Kategori</th>
                    <?php if($level == 'admin'): ?><th>Cabang</th><?php endif; ?>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($row = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-bold"><?= $row['nama_kategori'] ?></td>
                    
                    <?php if($level == 'admin'): ?>
                        <td>
                            <?php if(!empty($row['nama_cabang'])): ?>
                                 <span class="badge bg-info"><?= $row['nama_cabang'] ?></span>
                            <?php else: ?>
                                 <span class="badge bg-secondary">Global / Pusat</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal<?= $row['id'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="javascript:void(0);" onclick="confirmDelete('<?= $row['id'] ?>')" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>

                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header border-0"><h5 class="modal-title">Edit Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <label class="form-label">Nama Kategori</label>
                                    <input type="text" name="nama_kategori" class="form-control" value="<?= $row['nama_kategori'] ?>" required>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="submit" name="edit" class="btn btn-primary btn-sm w-100">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header border-0"><h5 class="modal-title">Kategori Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" required placeholder="Cth: Minuman">
                    </div>
                    
                    <?php if($level == 'admin'): ?>
                    <div class="mb-3 bg-light p-2 rounded">
                        <label class="small text-muted fw-bold">Simpan ke (Opsional)</label>
                        <select name="cabang_override" class="form-select form-select-sm">
                            <option value="">Global / Pusat (Semua Cabang)</option>
                            <?php 
                            if(isset($list_cabang)) {
                                $list_cabang->data_seek(0);
                                while($c = $list_cabang->fetch_assoc()): 
                            ?>
                                <option value="<?= $c['id'] ?>" <?= ($cabang_id == $c['id']) ? 'selected' : '' ?>>
                                    <?= $c['nama_cabang'] ?>
                                </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah" class="btn btn-primary btn-sm w-100">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Kategori?', text: "Menu di dalamnya mungkin ikut terhapus!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = "?hapus=" + id; }
        })
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>