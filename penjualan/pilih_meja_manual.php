<?php
// Cek status sesi, jika belum aktif, jalankan.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../auth/koneksi.php';

// Jika belum login, tendang ke halaman login utama
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_cabang = $_SESSION['cabang_id'] ?? 0;
$level = $_SESSION['level'] ?? '';

// Ambil data meja di cabang ini
$sql = "SELECT * FROM meja WHERE status = 'kosong'";
if ($level != 'admin') {
    $sql .= " AND cabang_id = '$user_cabang'";
}
$meja_list = $koneksi->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Meja Manual - Mode Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow-sm border-0" style="max-width: 400px; width: 95%;">
        <div class="card-body p-4 text-center">
            <div class="mb-3">
                <i class="fas fa-user-tie fa-3x text-primary"></i>
            </div>
            <h5 class="mb-1 fw-bold">Mode Pesanan Manual</h5>
            <p class="text-muted small mb-4">Bantu pelanggan memilih meja tanpa scan QR.</p>
            
            <form action="set_meja_manual.php" method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">Pilih Meja Kosong</label>
                    <select name="meja_id" class="form-select" required>
                        <option value="">-- Pilih Meja --</option>
                        <?php if($meja_list->num_rows > 0): ?>
                            <?php while($m = $meja_list->fetch_assoc()): ?>
                                <option value="<?= $m['id'] ?>">Meja <?= $m['nomor_meja'] ?></option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ada meja kosong</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk ke Menu
                </button>
            </form>
            
            <hr>
            
            <div class="d-flex justify-content-between">
                <a href="../admin/laporan" class="text-decoration-none small text-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
                <a href="index.php?mode=scan" class="text-decoration-none small text-secondary">
                    Scan QR <i class="fas fa-qrcode ms-1"></i>
                </a>
            </div>
        </div>
    </div>

</body>
</html>