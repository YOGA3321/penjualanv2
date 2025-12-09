<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'gudang' && $_SESSION['level'] != 'admin')) { 
    header("Location: ../login.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Barang Masuk";
$active_menu = "barang_masuk";

// --- PROSES SIMPAN TRANSAKSI ---
if (isset($_POST['simpan_masuk'])) {
    $pemasok_id = $_POST['pemasok_id'];
    $tanggal = $_POST['tanggal_masuk'];
    $user_id = $_SESSION['user_id'];
    $keterangan = $koneksi->real_escape_string($_POST['keterangan']);
    
    // Upload Nota (Optional)
    $bukti_nota = NULL;
    if(!empty($_FILES['bukti_nota']['name'])) {
        $target_dir = "../assets/images/nota/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES['bukti_nota']['name'], PATHINFO_EXTENSION);
        $filename = "IN-" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['bukti_nota']['tmp_name'], $target_dir . $filename);
        $bukti_nota = "assets/images/nota/" . $filename;
    }

    // 1. Insert Header
    $koneksi->query("INSERT INTO barang_masuk (pemasok_id, user_id, tanggal_masuk, bukti_nota, keterangan) VALUES ('$pemasok_id', '$user_id', '$tanggal', '$bukti_nota', '$keterangan')");
    $masuk_id = $koneksi->insert_id;
    
    // 2. Insert Details & Update Stok
    $items = $_POST['items'];
    $qtys = $_POST['qtys'];
    
    for ($i = 0; $i < count($items); $i++) {
        $item_id = $items[$i];
        $qty = $qtys[$i];
        
        if ($qty > 0) {
            // Insert Detail
            $koneksi->query("INSERT INTO barang_masuk_detail (barang_masuk_id, item_id, qty) VALUES ('$masuk_id', '$item_id', '$qty')");
            
            // Update Stok Master
            $koneksi->query("UPDATE gudang_items SET stok = stok + $qty WHERE id='$item_id'");
            
            // Log Mutasi
            $koneksi->query("INSERT INTO mutasi_gudang (item_id, jenis_mutasi, qty, keterangan) VALUES ('$item_id', 'masuk_supplier', '$qty', 'Barang Masuk #$masuk_id')");
        }
    }
    
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Barang masuk tercatat & stok bertambah'];
    header("Location: barang_masuk.php"); exit;
}

$pemasok = $koneksi->query("SELECT * FROM pemasok ORDER BY nama_pemasok ASC");
$items = $koneksi->query("SELECT * FROM gudang_items ORDER BY nama_item ASC");
$riwayat = $koneksi->query("SELECT bm.*, p.nama_pemasok, u.nama as staff FROM barang_masuk bm JOIN pemasok p ON bm.pemasok_id = p.id JOIN users u ON bm.user_id = u.id ORDER BY bm.tanggal_masuk DESC LIMIT 10");

include '../layouts/admin/header.php';
?>

<div class="row">
    <!-- Form Input Pemasukan -->
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white fw-bold"><i class="fas fa-download me-2"></i>Input Barang Masuk</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>Pemasok / Supplier</label>
                        <select name="pemasok_id" class="form-select" required>
                            <option value="">-- Pilih Pemasok --</option>
                            <?php foreach($pemasok as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nama_pemasok'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2 text-end">
                             <a href="pemasok.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i> Tambah Supplier Baru</a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-2">Item Barang</label>
                        <div id="items-container">
                            <div class="item-row row g-2 mb-2">
                                <div class="col-8">
                                    <select name="items[]" class="form-select" required>
                                        <option value="">-- Pilih Item --</option>
                                        <?php foreach($items as $it): ?>
                                        <option value="<?= $it['id'] ?>"><?= $it['nama_item'] ?> (<?= $it['satuan'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="qtys[]" class="form-control" placeholder="Qty" required min="1">
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success w-100 dashed-border" onclick="addItem()">
                            <i class="fas fa-plus me-1"></i> Tambah Baris Item
                        </button>
                    </div>

                    <div class="mb-3">
                        <label>Foto Nota / Surat Jalan (Opsional)</label>
                        <input type="file" name="bukti_nota" class="form-control">
                    </div>
                    
                     <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>

                    <button type="submit" name="simpan_masuk" class="btn btn-success w-100 fw-bold py-2">
                        <i class="fas fa-save me-2"></i> Simpan Stok Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Pemasukan -->
    <div class="col-lg-7">
        <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-history me-2"></i>Riwayat Barang Masuk</h5>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Pemasok</th>
                                <th>Item</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($riwayat as $r): ?>
                            <tr>
                                <td class="ps-4 small"><?= date('d/m/Y', strtotime($r['tanggal_masuk'])) ?></td>
                                <td class="fw-bold"><?= $r['nama_pemasok'] ?></td>
                                <td>
                                    <?php 
                                    $id_masuk = $r['id'];
                                    $det = $koneksi->query("SELECT d.qty, i.nama_item FROM barang_masuk_detail d JOIN gudang_items i ON d.item_id=i.id WHERE barang_masuk_id='$id_masuk'");
                                    foreach($det as $d) echo "<div class='small text-muted'>- " . $d['nama_item'] . " (" . $d['qty'] . ")</div>";
                                    ?>
                                </td>
                                <td>
                                    <?php if($r['bukti_nota']): ?>
                                        <a href="../<?= $r['bukti_nota'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="fas fa-image"></i></a>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Clone logic
    const itemOptions = `<?php foreach($items as $it): ?><option value="<?= $it['id'] ?>"><?= $it['nama_item'] ?> (<?= $it['satuan'] ?>)</option><?php endforeach; ?>`;
    function addItem() {
        const div = document.createElement('div');
        div.className = 'item-row row g-2 mb-2';
        div.innerHTML = `<div class="col-8"><select name="items[]" class="form-select" required><option value="">-- Pilih Item --</option>${itemOptions}</select></div><div class="col-4"><div class="input-group"><input type="number" name="qtys[]" class="form-control" placeholder="Qty" required min="1"><button type="button" class="btn btn-danger" onclick="this.closest('.row').remove()"><i class="fas fa-times"></i></button></div></div>`;
        document.getElementById('items-container').appendChild(div);
    }
</script>

<?php include '../layouts/admin/header.php'; ?>
