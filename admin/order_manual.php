<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Pesanan Manual";
$active_menu = "order_manual"; // Penanda menu aktif

$user_cabang = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';

// --- PROSES SET MEJA ---
if (isset($_POST['mulai_pesan'])) {
    $meja_id = $_POST['meja_id'];
    
    // Ambil detail meja
    $query = "SELECT meja.*, cabang.nama_cabang, cabang.id as id_cabang 
              FROM meja 
              JOIN cabang ON meja.cabang_id = cabang.id 
              WHERE meja.id = '$meja_id'";
              
    $result = $koneksi->query($query);
    
    if ($result->num_rows > 0) {
        $info = $result->fetch_assoc();
        
        // SET SESSION SEBAGAI PELANGGAN
        $_SESSION['plg_meja_id'] = $info['id'];
        $_SESSION['plg_no_meja'] = $info['nomor_meja'];
        $_SESSION['plg_cabang_id'] = $info['id_cabang'];
        $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
        
        // Redirect ke Frontend Menu
        header("Location: ../penjualan/"); 
        exit;
    } else {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data meja tidak valid.'];
    }
}

// AMBIL LIST MEJA KOSONG
$sql = "SELECT * FROM meja WHERE status = 'kosong'";
if ($level != 'admin') {
    $sql .= " AND cabang_id = '$user_cabang'";
} else {
    // Jika admin sedang view cabang tertentu
    if (isset($_SESSION['view_cabang_id'])) {
        $view_id = $_SESSION['view_cabang_id'];
        $sql .= " AND cabang_id = '$view_id'";
    }
}
$sql .= " ORDER BY CAST(nomor_meja AS UNSIGNED) ASC";
$meja_list = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4 text-center">
                <div class="mb-4 text-primary">
                    <i class="fas fa-cash-register fa-4x"></i>
                </div>
                <h4 class="fw-bold">Mode Kasir / Pesanan Manual</h4>
                <p class="text-muted mb-4">Pilih meja untuk membantu pelanggan melakukan pemesanan tanpa scan QR.</p>
                
                <form method="POST">
                    <div class="form-floating mb-3 text-start">
                        <select name="meja_id" class="form-select" id="selectMeja" required>
                            <option value="" selected disabled>Pilih salah satu...</option>
                            <?php if($meja_list->num_rows > 0): ?>
                                <?php while($m = $meja_list->fetch_assoc()): ?>
                                    <option value="<?= $m['id'] ?>">Meja <?= $m['nomor_meja'] ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option disabled>Semua meja penuh / Tidak ada data</option>
                            <?php endif; ?>
                        </select>
                        <label for="selectMeja">Pilih Meja Kosong</label>
                    </div>
                    
                    <button type="submit" name="mulai_pesan" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fas fa-utensils me-2"></i> Buka Menu Pemesanan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>