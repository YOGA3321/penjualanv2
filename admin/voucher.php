<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Manajemen Voucher";
$active_menu = "voucher"; // Tambahkan menu ini di header.php nanti

// --- TAMBAH VOUCHER ---
if(isset($_POST['tambah'])) {
    $kode = strtoupper($_POST['kode']); // Kode huruf besar
    $tipe = $_POST['tipe'];
    $nilai = $_POST['nilai'];
    $min = $_POST['min_belanja'];
    $stok = $_POST['stok'];
    $expired = $_POST['berlaku_sampai'];

    $stmt = $koneksi->prepare("INSERT INTO vouchers (kode, tipe, nilai, min_belanja, stok, berlaku_sampai) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddis", $kode, $tipe, $nilai, $min, $stok, $expired);
    
    if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Voucher dibuat'];
    else $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Kode voucher mungkin sudah ada'];
    header("Location: voucher.php"); exit;
}

// --- HAPUS ---
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM vouchers WHERE id='$id'");
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Dihapus', 'text'=>'Voucher dihapus'];
    header("Location: voucher.php"); exit;
}

$data = $koneksi->query("SELECT * FROM vouchers ORDER BY id DESC");

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-ticket-alt me-2"></i>Voucher & Diskon</h4>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Buat Voucher
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-4">Kode</th>
                    <th>Potongan</th>
                    <th>Min. Belanja</th>
                    <th>Sisa Stok</th>
                    <th>Expired</th>
                    <th class="text-end px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $data->fetch_assoc()): ?>
                <tr>
                    <td class="px-4 fw-bold text-primary"><?= $row['kode'] ?></td>
                    <td>
                        <?php if($row['tipe']=='percent'): ?>
                            <span class="badge bg-warning text-dark"><?= $row['nilai'] ?>%</span>
                        <?php else: ?>
                            <span class="badge bg-success">Rp <?= number_format($row['nilai']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>Rp <?= number_format($row['min_belanja']) ?></td>
                    <td><?= $row['stok'] ?></td>
                    <td>
                        <?php 
                            $exp = strtotime($row['berlaku_sampai']);
                            $now = time();
                            $cls = ($exp < $now) ? 'text-danger fw-bold' : 'text-muted';
                            echo "<span class='$cls'>".date('d M Y', $exp)."</span>";
                        ?>
                    </td>
                    <td class="text-end px-4">
                        <a href="voucher.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus voucher ini?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-white">
            <div class="modal-header"><h5 class="modal-title fw-bold">Buat Voucher Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Kode Voucher</label>
                    <div class="input-group">
                        <input type="text" name="kode" id="kode_input" class="form-control text-uppercase fw-bold" placeholder="CTH: DISKON10" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">Acak</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>Tipe Potongan</label>
                        <select name="tipe" class="form-select">
                            <option value="fixed">Nominal (Rp)</option>
                            <option value="percent">Persen (%)</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label>Nilai Potongan</label>
                        <input type="number" name="nilai" class="form-control" placeholder="10000 atau 10" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Minimal Belanja (Rp)</label>
                    <input type="number" name="min_belanja" class="form-control" value="0">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>Jumlah Kuota</label>
                        <input type="number" name="stok" class="form-control" value="100">
                    </div>
                    <div class="col-6 mb-3">
                        <label>Berlaku Sampai</label>
                        <input type="date" name="berlaku_sampai" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<script>
function generateCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('kode_input').value = result;
}
</script>

<?php include '../layouts/admin/footer.php'; ?>