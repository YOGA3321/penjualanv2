<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'gudang') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Stok Gudang";
$active_menu = "inventory";

// --- CRUD ITEMS ---
if (isset($_POST['tambah_item'])) {
    $nama = $_POST['nama_item'];
    $satuan = $_POST['satuan'];
    $stok = $_POST['stok'];
    $jenis = $_POST['jenis'];
    $koneksi->query("INSERT INTO gudang_items (nama_item, satuan, stok, jenis) VALUES ('$nama', '$satuan', '$stok', '$jenis')");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Item ditambahkan'];
    header("Location: inventory.php"); exit;
}

if (isset($_POST['edit_item'])) {
    $id = $_POST['edit_id'];
    $nama = $_POST['edit_nama'];
    $satuan = $_POST['edit_satuan'];
    $jenis = $_POST['edit_jenis'];
    $koneksi->query("UPDATE gudang_items SET nama_item='$nama', satuan='$satuan', jenis='$jenis' WHERE id='$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Update', 'text'=>'Data item diperbarui'];
    header("Location: inventory.php"); exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM gudang_items WHERE id='$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Item dihapus'];
    header("Location: inventory.php"); exit;
}

// --- RESTOCK (Masuk Supplier) ---
if (isset($_POST['restock'])) {
    $id = $_POST['item_id'];
    $qty = $_POST['qty'];
    $ket = $_POST['keterangan'];
    
    // 1. Insert Mutasi
    $koneksi->query("INSERT INTO mutasi_gudang (item_id, jenis_mutasi, qty, keterangan) VALUES ('$id', 'masuk_supplier', '$qty', '$ket')");
    
    // 2. Update Stok
    $koneksi->query("UPDATE gudang_items SET stok = stok + $qty WHERE id='$id'");
    
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Restock Berhasil', 'text'=>'Stok telah ditambahkan'];
    header("Location: inventory.php"); exit;
}

// --- PROCES PRODUKSI (Bahan -> Jadi) ---
if (isset($_POST['produksi'])) {
    $bahan_id = $_POST['bahan_id'];
    $qty_bahan = $_POST['qty_bahan'];
    $produk_id = $_POST['produk_id'];
    $qty_produk = $_POST['qty_produk'];
    
    // Cek stok bahan
    $stok_bahan = $koneksi->query("SELECT stok FROM gudang_items WHERE id='$bahan_id'")->fetch_assoc()['stok'];
    if ($stok_bahan < $qty_bahan) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Stok bahan baku tidak cukup!'];
    } else {
        // Kurangi Bahan
        $koneksi->query("INSERT INTO mutasi_gudang (item_id, jenis_mutasi, qty, keterangan) VALUES ('$bahan_id', 'keluar_produksi', '$qty_bahan', 'Digunakan untuk produksi')");
        $koneksi->query("UPDATE gudang_items SET stok = stok - $qty_bahan WHERE id='$bahan_id'");
        
        // Tambah Produk
        $koneksi->query("INSERT INTO mutasi_gudang (item_id, jenis_mutasi, qty, keterangan) VALUES ('$produk_id', 'masuk_produksi', '$qty_produk', 'Hasil produksi')");
        $koneksi->query("UPDATE gudang_items SET stok = stok + $qty_produk WHERE id='$produk_id'");
        
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Produksi Selesai', 'text'=>'Stok produk jadi bertambah'];
    }
    header("Location: inventory.php"); exit;
}

$items = $koneksi->query("SELECT * FROM gudang_items ORDER BY jenis ASC, nama_item ASC");
$bahans = $koneksi->query("SELECT * FROM gudang_items WHERE jenis='bahan_baku'");
$produks = $koneksi->query("SELECT * FROM gudang_items WHERE jenis='produk_jadi'");

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-primary"><i class="fas fa-boxes me-2"></i>Manajemen Stok Gudang</h4>
    <div>
        <button class="btn btn-warning text-white fw-bold me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProduksi">
            <i class="fas fa-industry me-2"></i> Produksi (Kitchen)
        </button>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus me-2"></i> Item Baru
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Nama Item</th>
                        <th>Jenis</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Update Terakhir</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $row): ?>
                    <tr>
                        <td class="px-4 fw-bold text-dark"><?= $row['nama_item'] ?></td>
                        <td>
                            <?php if($row['jenis']=='bahan_baku'): ?>
                                <span class="badge bg-secondary">Bahan Baku</span>
                            <?php else: ?>
                                <span class="badge bg-success">Produk Jadi</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-bold <?= $row['stok']<10?'text-danger':'' ?>"><?= $row['stok'] ?></span></td>
                        <td><?= $row['satuan'] ?></td>
                        <td class="small text-muted"><?= $row['updated_at'] ?></td>
                        <td class="text-end px-4">
                            <button class="btn btn-sm btn-success me-1" onclick="openRestock(<?= $row['id'] ?>, '<?= $row['nama_item'] ?>')" title="Restock / Barang Masuk"><i class="fas fa-plus-circle"></i></button>
                            <button class="btn btn-sm btn-info text-white me-1" onclick="openEdit(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="fas fa-edit"></i></button>
                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus item ini?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Item -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Item Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label>Nama Item</label><input type="text" name="nama_item" class="form-control" required></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label>Satuan (kg/pcs/l)</label><input type="text" name="satuan" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label>Stok Awal</label><input type="number" name="stok" class="form-control" value="0"></div>
                </div>
                <div class="mb-3"><label>Jenis</label>
                    <select name="jenis" class="form-select">
                        <option value="bahan_baku">Bahan Baku (Raw Material)</option>
                        <option value="produk_jadi">Produk Jadi (Finished Good)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah_item" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<!-- Modal Edit Item -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Item</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="mb-3"><label>Nama Item</label><input type="text" name="edit_nama" id="edit_nama" class="form-control" required></div>
                <div class="mb-3"><label>Satuan</label><input type="text" name="edit_satuan" id="edit_satuan" class="form-control" required></div>
                <div class="mb-3"><label>Jenis</label>
                    <select name="edit_jenis" id="edit_jenis" class="form-select">
                        <option value="bahan_baku">Bahan Baku</option>
                        <option value="produk_jadi">Produk Jadi</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_item" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<!-- Modal Restock -->
<div class="modal fade" id="modalRestock" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Restock Barang Masuk</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="item_id" id="restock_id">
                <p>Menambahkan stok untuk: <strong id="restock_nama" class="text-primary"></strong></p>
                <div class="mb-3"><label>Jumlah (Qty)</label><input type="number" name="qty" class="form-control" required min="1"></div>
                <div class="mb-3"><label>Keterangan (Supplier/Sumber)</label><textarea name="keterangan" class="form-control" required placeholder="Contoh: Beli dari Pasar"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" name="restock" class="btn btn-success">Proses Restock</button></div>
        </form>
    </div>
</div>

<!-- Modal Produksi -->
<div class="modal fade" id="modalProduksi" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Pencatatan Produksi</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="fas fa-info-circle me-1"></i> Stok bahan baku akan berkurang, dan stok produk jadi akan bertambah.
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Pilih Bahan Baku</label>
                        <select name="bahan_id" class="form-select" required>
                            <?php foreach($bahans as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= $b['nama_item'] ?> (Stok: <?= $b['stok'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Qty Dipakai</label>
                        <input type="number" name="qty_bahan" class="form-control" required min="1">
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Hasil Produk Jadi</label>
                        <select name="produk_id" class="form-select" required>
                            <?php foreach($produks as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nama_item'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Qty Dihasilkan</label>
                        <input type="number" name="qty_produk" class="form-control" required min="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="produksi" class="btn btn-warning text-white fw-bold">Simpan Produksi</button></div>
        </form>
    </div>
</div>

<script>
function openEdit(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_nama').value = item.nama_item;
    document.getElementById('edit_satuan').value = item.satuan;
    document.getElementById('edit_jenis').value = item.jenis;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
function openRestock(id, nama) {
    document.getElementById('restock_id').value = id;
    document.getElementById('restock_nama').innerText = nama;
    new bootstrap.Modal(document.getElementById('modalRestock')).show();
}
</script>

<?php include '../layouts/admin/footer.php'; ?>
