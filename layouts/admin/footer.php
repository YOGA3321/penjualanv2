</main> 
</div> 

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header bg-light">
        <h5 class="offcanvas-title fw-bold"><i class="fas fa-bars me-2"></i>Menu Navigasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="sidebar-nav">
            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-3 mb-2">Menu Utama</p>
            <ul class="list-unstyled">
                <li><a href="index" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a></li>
                <li><a href="laporan" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-chart-line fa-fw me-2"></i> Laporan</a></li>
                <li><a href="riwayat" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-history fa-fw me-2"></i> Riwayat Transaksi</a></li>
                <li><a href="menu" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-utensils fa-fw me-2"></i> Manajemen Menu</a></li>
                
                <li><a href="reservasi" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-calendar-alt fa-fw me-2"></i> Data Reservasi</a></li>

                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <li><a href="kategori" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tags fa-fw me-2"></i> Kategori Menu</a></li>
                    <li><a href="users" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-users-cog fa-fw me-2"></i> Manajemen User</a></li>
                    <li><a href="cabang" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-store-alt fa-fw me-2"></i> Manajemen Cabang</a></li>
                <?php endif; ?>
            </ul>

            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-4 mb-2">Operasional</p>
            <ul class="list-unstyled">
                <li><a href="transaksi_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-bell fa-fw me-2"></i> Pesanan Masuk</a></li>
                <li><a href="order_manual" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tablet-alt fa-fw me-2"></i> Pesanan Manual</a></li>
                <li><a href="dapur" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-fire fa-fw me-2"></i> Monitor Dapur</a></li>
                <li><a href="meja" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-chair fa-fw me-2"></i> Manajemen Meja</a></li>
            </ul>

            <hr class="my-4">
            <div class="px-4 mb-4">
                <a href="../logout" class="btn btn-outline-danger w-100 fw-bold"><i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout</a>
            </div>
        </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
$sse_cabang = 'pusat';
if (isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    $sse_cabang = $_SESSION['view_cabang_id'] ?? 'pusat';
} elseif(isset($_SESSION['cabang_id'])) {
    $sse_cabang = $_SESSION['cabang_id'];
}

// Cek Level untuk Notifikasi
$is_karyawan = (isset($_SESSION['level']) && $_SESSION['level'] == 'karyawan');
?>

<script>
    let globalEventSource = null;

    function initGlobalConnection() {
        const statusDot = document.getElementById('statusIndicator');
        const onlineCount = document.getElementById('onlineCount');

        globalEventSource = new EventSource('api/sse_channel.php?cabang_id=<?= $sse_cabang ?>');

        globalEventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if(onlineCount) {
                onlineCount.innerText = data.online_users;
            }

            // [FIX] NOTIFIKASI RESERVASI (HANYA KARYAWAN & DI POJOK KANAN BAWAH)
            <?php if($is_karyawan): ?>
            if(data.reservasi_alert && data.reservasi_alert.length > 0) {
                data.reservasi_alert.forEach(msg => {
                    // Gunakan Toast SweetAlert (Bottom End)
                    const Toast = Swal.mixin({
                        toast: true, 
                        position: 'bottom-end', // Pojok Kanan Bawah
                        showConfirmButton: true, // Tombol Konfirmasi
                        confirmButtonText: 'Sudah',
                        confirmButtonColor: '#198754',
                        timer: 10000, // Muncul 10 detik
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });
                    Toast.fire({ icon: 'info', title: msg });
                });
            }
            <?php endif; ?>
        };

        // ... (Sisa error handling sama) ...
    }

    document.addEventListener('DOMContentLoaded', () => {
        initGlobalConnection();
    });
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['swal']['icon'] ?>',
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        timer: 2000, showConfirmButton: false
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>

</body>
</html>