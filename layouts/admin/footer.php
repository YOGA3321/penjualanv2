</main> 
</div> 

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header bg-light">
        <h5 class="offcanvas-title fw-bold"><i class="fas fa-bars me-2"></i>Menu Navigasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php 
            // Use same logic as desktop sidebar
            $current_uri = $_SERVER['REQUEST_URI'];
            $in_gudang = strpos($current_uri, '/gudang/') !== false;
            $user_lv = $_SESSION['level'] ?? '';
            $mobile_show_gudang = ($user_lv == 'gudang' || ($user_lv == 'admin' && $in_gudang));
        ?>
        
        <nav class="sidebar-nav">
            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-3 mb-2">
                <?= $mobile_show_gudang ? 'Menu Gudang' : 'Menu Utama' ?>
            </p>
            
            <?php if($mobile_show_gudang): ?>
                <!-- WAREHOUSE MENU -->
                <ul class="list-unstyled">
                    <li><a href="../gudang/index" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a></li>
                    <li><a href="../gudang/inventory" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-boxes fa-fw me-2"></i> Manajemen Stok</a></li>
                    <li><a href="../gudang/barang_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-download fa-fw me-2"></i> Barang Masuk</a></li>
                    <li><a href="../gudang/permintaan_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-inbox fa-fw me-2"></i> Permintaan Masuk</a></li>
                    <li><a href="../gudang/pemasok" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-truck fa-fw me-2"></i> Data Pemasok</a></li>
                </ul>
            <?php else: ?>
                <!-- ADMIN MENU -->
                <ul class="list-unstyled">
                    <li><a href="../admin/index" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a></li>
                    <li><a href="../admin/laporan" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-chart-line fa-fw me-2"></i> Laporan</a></li>
                    <li><a href="../admin/riwayat" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-history fa-fw me-2"></i> Riwayat Transaksi</a></li>
                    <li><a href="../admin/menu" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-utensils fa-fw me-2"></i> Manajemen Menu</a></li>
                    <li><a href="../admin/reservasi" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-calendar-alt fa-fw me-2"></i> Data Reservasi</a></li>

                    <?php if($user_lv == 'admin'): ?>
                        <li><a href="../admin/kategori" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tags fa-fw me-2"></i> Kategori Menu</a></li>
                        <li><a href="../admin/users" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-users-cog fa-fw me-2"></i> Manajemen User</a></li>
                        <li><a href="../admin/cabang" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-store-alt fa-fw me-2"></i> Manajemen Cabang</a></li>
                    <?php endif; ?>
                </ul>

                <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-4 mb-2">Operasional</p>
                <ul class="list-unstyled">
                    <li><a href="../admin/transaksi_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-bell fa-fw me-2"></i> Pesanan Masuk</a></li>
                    <li><a href="../admin/order_manual" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tablet-alt fa-fw me-2"></i> Pesanan Manual</a></li>
                    <li><a href="../admin/dapur" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-fire fa-fw me-2"></i> Monitor Dapur</a></li>
                    <li><a href="../admin/meja" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-chair fa-fw me-2"></i> Manajemen Meja</a></li>

                    <?php if($user_lv == 'admin'): ?>
                        <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-4 mb-2">Logistik</p>
                        <li><a href="../admin/request_stok" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-box-open fa-fw me-2"></i> Request Stok</a></li>
                        <li><a href="../admin/penerimaan_barang" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-clipboard-check fa-fw me-2"></i> Penerimaan Barang</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

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
    var globalEventSource = window.globalEventSource || null;

    function initGlobalConnection() {
        const statusDot = document.getElementById('statusIndicator');
        const onlineCount = document.getElementById('onlineCount');

        // Detect Module
        const isGudang = window.location.href.includes('/gudang');
        const moduleParam = isGudang ? 'gudang' : 'admin';

        globalEventSource = new EventSource('api/sse_channel?cabang_id=<?= $sse_cabang ?>&module=' + moduleParam);

        globalEventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if(onlineCount) {
                onlineCount.innerText = data.online_users;
            }

            // Dispatch System Event for Page-Specific Logic (e.g. Gudang)
            window.dispatchEvent(new CustomEvent('sse-data', { detail: data }));

            // Add Green Indicator when data flows
            if(statusDot) {
                statusDot.classList.remove('bg-secondary');
                statusDot.classList.add('bg-success');
                statusDot.classList.add('shadow-active'); // Add glowing effect class if you want
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
        
        globalEventSource.onerror = function() {
             if(statusDot) {
                statusDot.classList.remove('bg-success');
                statusDot.classList.add('bg-secondary');
            }
        };

        // ... (Sisa error handling sama) ...
    }

    // [SPA FIX] Gunakan turbo:load agar jalan setiap navigasi
    document.addEventListener('turbo:load', () => {
        // Close existing connection if any (to prevent duplicates)
        if(globalEventSource) {
            globalEventSource.close();
        }
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