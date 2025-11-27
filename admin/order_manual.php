<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Pesanan Manual (Kasir)";
$active_menu = "order_manual";

// --- PROSES LANJUT KE KASIR ---
if (isset($_POST['mulai_kasir'])) {
    $meja_id = $_POST['meja_id'];
    $nama_pelanggan = htmlspecialchars($_POST['nama_pelanggan']); // Input Nama Manual
    
    // Validasi Meja
    $cek = $koneksi->query("SELECT * FROM meja WHERE id = '$meja_id'")->fetch_assoc();
    
    if ($cek) {
        // Set Session Khusus Kasir
        $_SESSION['kasir_meja_id'] = $meja_id;
        $_SESSION['kasir_no_meja'] = $cek['nomor_meja'];
        $_SESSION['kasir_nama_pelanggan'] = $nama_pelanggan;
        $_SESSION['kasir_cabang_id'] = $cek['cabang_id']; // Penting utk filter menu
        
        // Redirect ke Halaman Kasir Utama
        header("Location: kasir_transaksi.php"); 
        exit;
    }
}

// Ambil Meja Kosong sesuai cabang Admin/Karyawan
$user_cabang = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'];

$sql = "SELECT * FROM meja WHERE status = 'kosong'";
if ($level != 'admin') {
    $sql .= " AND cabang_id = '$user_cabang'";
} elseif (isset($_SESSION['view_cabang_id'])) {
    $sql .= " AND cabang_id = '".$_SESSION['view_cabang_id']."'";
}
$meja_list = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i>Mulai Transaksi Baru</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Pelanggan</label>
                        <input type="text" name="nama_pelanggan" class="form-control" placeholder="Cth: Bapak Budi / Non-Member" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Pilih Meja</label>
                        <select name="meja_id" class="form-select" required>
                            <option value="" selected disabled>-- Pilih Meja Kosong --</option>
                            <?php while($m = $meja_list->fetch_assoc()): ?>
                                <option value="<?= $m['id'] ?>">Meja <?= $m['nomor_meja'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="mulai_kasir" class="btn btn-primary w-100 py-2 fw-bold">
                        Lanjut ke Menu & Pembayaran <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>