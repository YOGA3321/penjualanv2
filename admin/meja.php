<?php
// 1. Konfigurasi Halaman (Sebelum include header)
require_once '../auth/koneksi.php';

$page_title = "Manajemen Meja";
$active_menu = "meja";

// Tombol yang akan muncul di pojok kanan atas (dikirim ke header.php)
$header_action_btn = '
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mejaModal">
    <i class="fas fa-plus me-2"></i>Tambah Meja
</button>';

// 2. Logika PHP (Insert/Delete/Select) - Sama seperti sebelumnya
if (isset($_POST['tambah_meja'])) {
    $nomor_meja = $_POST['nomor_meja'];
    $cabang_id  = $_POST['cabang_id'];
    $token = bin2hex(random_bytes(16));
    
    $query = "INSERT INTO meja (cabang_id, nomor_meja, qr_token, status) VALUES (?, ?, ?, 'kosong')";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("iss", $cabang_id, $nomor_meja, $token);
    
    if ($stmt->execute()) {
        $sukses = "Meja berhasil ditambahkan!";
    } else {
        $error = "Gagal: " . $koneksi->error;
    }
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $koneksi->query("DELETE FROM meja WHERE id = '$id_hapus'");
    header("Location: meja"); // Perhatikan: tanpa .php karena ada .htaccess
    exit;
}

// Ambil Data
$cabangs = $koneksi->query("SELECT * FROM cabang");
$filter_cabang = isset($_GET['filter_cabang']) ? $_GET['filter_cabang'] : '';
$sql_meja = "SELECT meja.*, cabang.nama_cabang FROM meja JOIN cabang ON meja.cabang_id = cabang.id";
if ($filter_cabang) { $sql_meja .= " WHERE meja.cabang_id = '$filter_cabang'"; }
$sql_meja .= " ORDER BY cabang.nama_cabang ASC, meja.nomor_meja ASC";
$data_meja = $koneksi->query($sql_meja);

// 3. INCLUDE HEADER
include '../layouts/admin/header.php';
?>

<?php if(isset($sukses)): ?>
    <div class="alert alert-success"><?= $sukses ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body d-flex align-items-center">
         <label for="filterCabang" class="form-label me-3 mb-0">Filter Cabang:</label>
         <form method="GET" action="" id="formFilter">
             <select class="form-select w-auto" name="filter_cabang" onchange="document.getElementById('formFilter').submit()">
                <option value="">Semua Cabang</option>
                <?php foreach($cabangs as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filter_cabang == $c['id'] ? 'selected' : '' ?>>
                        <?= $c['nama_cabang'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
         </form>
    </div>
</div>

<div class="row">
    <?php if ($data_meja->num_rows > 0): ?>
        <?php while($row = $data_meja->fetch_assoc()): ?>
            <?php 
                $bg_badge = $row['status'] == 'kosong' ? 'bg-success' : 'bg-danger';
                $icon_color = $row['status'] == 'kosong' ? 'text-success' : 'text-danger';
                // URL menggunakan https dan path yang sesuai
                $order_url = "https://" . $_SERVER['HTTP_HOST'] . "/penjualanv2/pelanggan/order?token=" . $row['qr_token'];
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="card table-card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-chair table-icon <?= $icon_color ?>"></i>
                        <h5 class="card-title mt-3">Meja <?= $row['nomor_meja'] ?></h5>
                        <small class="text-muted d-block mb-2"><?= $row['nama_cabang'] ?></small>
                        <span class="badge <?= $bg_badge ?>"><?= ucfirst($row['status']) ?></span>
                    </div>
                    <div class="card-footer bg-white border-0">
                         <button class="btn btn-sm btn-outline-dark" 
                                 onclick="showQR('<?= $row['nomor_meja'] ?>', '<?= $row['nama_cabang'] ?>', '<?= $order_url ?>')">
                             <i class="fas fa-qrcode"></i> QR
                         </button>
                         <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">
                             <i class="fas fa-trash"></i>
                         </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5"><p class="text-muted">Belum ada data meja.</p></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="mejaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Meja Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nomor Meja</label>
                        <input type="text" name="nomor_meja" class="form-control" placeholder="Contoh: 01A" required>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">Lokasi Cabang</label>
                        <select class="form-select" name="cabang_id" required>
                            <?php 
                            $cabangs->data_seek(0); 
                            while($c = $cabangs->fetch_assoc()): 
                            ?>
                                <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_meja" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center pt-0">
                <h5 id="qrMejaTitle" class="mb-1 fw-bold">Meja X</h5>
                <p id="qrCabangTitle" class="text-muted small mb-3">Cabang X</p>
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <p class="small text-muted" style="font-size: 10px; word-break: break-all;" id="urlText"></p>
                <button class="btn btn-primary btn-sm w-100" onclick="window.print()"><i class="fas fa-print me-2"></i> Cetak Label</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showQR(nomor, cabang, url) {
        document.getElementById('qrMejaTitle').innerText = "Meja " + nomor;
        document.getElementById('qrCabangTitle').innerText = cabang;
        document.getElementById('urlText').innerText = url;
        document.getElementById('qrcode').innerHTML = "";
        new QRCode(document.getElementById("qrcode"), { text: url, width: 180, height: 180, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H });
        var myModal = new bootstrap.Modal(document.getElementById('qrModal'));
        myModal.show();
    }
</script>

<?php 
// 5. INCLUDE FOOTER
include '../layouts/admin/footer.php'; 
?>