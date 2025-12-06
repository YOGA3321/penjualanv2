<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'pelanggan') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$uid = $_SESSION['user_id'];
$u = $koneksi->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .poin-card { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border-radius: 15px; padding: 20px; }
        .menu-btn { border-radius: 15px; padding: 20px; height: 100%; border: none; transition: 0.2s; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .menu-btn:active { transform: scale(0.98); }
        #reader { width: 100%; border-radius: 15px; overflow: hidden; display: none; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <?php if(!empty($u['foto'])): ?>
                <img src="<?= $u['foto'] ?>" class="rounded-circle shadow-sm" width="50" height="50" style="object-fit: cover;">
            <?php else: ?>
                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center shadow-sm" style="width:50px; height:50px;"><i class="fas fa-user text-secondary"></i></div>
            <?php endif; ?>
            <div>
                <h5 class="mb-0 fw-bold">Halo, <?= htmlspecialchars(explode(' ', $u['nama'])[0]) ?>!</h5>
                <a href="profil.php" class="text-decoration-none small text-primary fw-bold">Edit Profil <i class="fas fa-pen ms-1"></i></a>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="poin-card shadow mb-4 position-relative overflow-hidden">
        <div class="position-relative z-1">
            <small class="text-white-50 text-uppercase fw-bold">Modern Poin</small>
            <h1 class="fw-bold mb-0"><?= number_format($u['poin']) ?> <span class="fs-6 fw-normal">pts</span></h1>
            <small class="d-block mt-2">Kumpulkan poin dari setiap pesanan!</small>
        </div>
        <i class="fas fa-coins fa-5x position-absolute text-white opacity-25" style="right: -10px; bottom: -10px;"></i>
    </div>

    <div id="reader" class="shadow"></div>

    <div class="row g-3">
        <div class="col-6">
            <button class="btn btn-light w-100 menu-btn text-primary" onclick="startScan()">
                <i class="fas fa-qrcode fa-3x mb-2"></i><br>
                <span class="fw-bold">Scan Meja</span>
            </button>
        </div>
        <div class="col-6">
            <a href="reservasi.php" class="btn btn-light w-100 menu-btn text-danger">
                <i class="fas fa-calendar-check fa-3x mb-2"></i><br>
                <span class="fw-bold">Reservasi</span>
            </a>
        </div>
        <div class="col-6">
            <a href="riwayat.php" class="btn btn-light w-100 menu-btn text-warning">
                <i class="fas fa-history fa-3x mb-2"></i><br>
                <span class="fw-bold">Riwayat</span>
            </a>
        </div>
        <div class="col-6">
            <button class="btn btn-light w-100 menu-btn text-success" onclick="Swal.fire('Coming Soon', 'Fitur Voucher sedang disiapkan', 'info')">
                <i class="fas fa-ticket-alt fa-3x mb-2"></i><br>
                <span class="fw-bold">Voucher</span>
            </button>
        </div>
    </div>

    <h6 class="mt-5 mb-3 fw-bold text-secondary">Aktivitas Reservasi</h6>
    
    <?php
    $q_res = $koneksi->query("SELECT r.*, m.nomor_meja, c.nama_cabang, c.id as id_cabang 
                              FROM reservasi r 
                              JOIN meja m ON r.meja_id = m.id
                              JOIN cabang c ON m.cabang_id = c.id
                              WHERE r.user_id = '$uid' 
                              AND r.status IN ('pending', 'checkin')
                              ORDER BY r.waktu_reservasi ASC");
    ?>

    <?php if($q_res->num_rows > 0): ?>
        <?php while($res = $q_res->fetch_assoc()): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-primary"><?= date('d M, H:i', strtotime($res['waktu_reservasi'])) ?></div>
                        <?php if($res['status'] == 'checkin'): ?>
                            <span class="badge bg-success">Siap Pesan</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Menunggu</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-dark fw-bold">Meja <?= $res['nomor_meja'] ?></div>
                            <small class="text-muted"><?= $res['nama_cabang'] ?></small>
                        </div>
                        
                        <?php if($res['status'] == 'checkin'): ?>
                            <form action="akses_pesan.php" method="POST">
                                <input type="hidden" name="meja_id" value="<?= $res['meja_id'] ?>">
                                <input type="hidden" name="no_meja" value="<?= $res['nomor_meja'] ?>">
                                <input type="hidden" name="cabang_id" value="<?= $res['id_cabang'] ?>">
                                <input type="hidden" name="nama_cabang" value="<?= $res['nama_cabang'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary fw-bold rounded-pill px-3">
                                    <i class="fas fa-utensils me-1"></i> Pesan Menu
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-muted small py-3">Belum ada reservasi aktif.</div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function startScan() {
        const reader = document.getElementById('reader');
        reader.style.display = 'block';
        
        const html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(
            { facingMode: "environment" }, 
            { fps: 10, qrbox: 250 },
            (decodedText, decodedResult) => {
                // STOP SCANNING
                html5QrCode.stop().then(() => {
                    reader.style.display = 'none';
                    // Redirect ke Login Check / Token Check
                    try {
                        const urlObj = new URL(decodedText);
                        const token = urlObj.searchParams.get("token");
                        if(token) {
                            window.location.href = `../penjualan/index.php?token=${token}`;
                        } else {
                            Swal.fire('Error', 'QR Code tidak valid', 'error');
                        }
                    } catch(e) {
                        Swal.fire('Error', 'Format QR salah', 'error');
                    }
                });
            },
            (errorMessage) => { /* ignore errors */ }
        ).catch(err => {
            Swal.fire('Gagal', 'Kamera tidak dapat diakses. Izinkan akses kamera.', 'error');
        });
    }
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['swal']['icon'] ?>',
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>'
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>

</body>
</html>