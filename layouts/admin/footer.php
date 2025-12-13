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
                <ul class="list-unstyled">
                    <li><a href="../gudang/index" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a></li>
                    <li><a href="../gudang/inventory" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-boxes fa-fw me-2"></i> Manajemen Stok</a></li>
                    <li><a href="../gudang/barang_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-download fa-fw me-2"></i> Barang Masuk</a></li>
                    <li><a href="../gudang/permintaan_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-inbox fa-fw me-2"></i> Permintaan Masuk</a></li>
                    <li><a href="../gudang/pemasok" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-truck fa-fw me-2"></i> Data Pemasok</a></li>
                </ul>
            <?php else: ?>
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
        // Mencegah duplikasi koneksi
        if(globalEventSource && globalEventSource.readyState !== 2) {
            return; 
        }

        const statusDot = document.getElementById('statusIndicator');
        const onlineCount = document.getElementById('onlineCount');

        // Detect Module
        const isGudang = window.location.href.includes('/gudang/');
        const moduleParam = isGudang ? 'gudang' : 'admin';
        
        // Setup Base URL (Menangani ../ dengan lebih aman)
        const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : ".." ?>';
        const sseUrl = `${baseUrl}/${moduleParam}/api/sse_channel.php?cabang_id=<?= $sse_cabang ?>&module=${moduleParam}`;

        console.log("Menghubungkan ke System Live: " + sseUrl); // Debugging

        globalEventSource = new EventSource(sseUrl);

        globalEventSource.onopen = function() {
            // Indikator Kuning (Menghubungkan)
            if(statusDot) {
                statusDot.classList.remove('bg-secondary', 'bg-success');
                statusDot.classList.add('bg-warning');
            }
        };

        globalEventSource.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                
                // Update User Online
                if(onlineCount) {
                    onlineCount.innerText = data.online_users;
                }

                // Indikator Hijau (Aktif)
                if(statusDot) {
                    statusDot.classList.remove('bg-secondary', 'bg-warning');
                    statusDot.classList.add('bg-success');
                }

                // Dispatch System Event untuk halaman lain (Gudang/Admin)
                window.dispatchEvent(new CustomEvent('sse-data', { detail: data }));

                // [FITUR] NOTIFIKASI RESERVASI (KHUSUS KARYAWAN)
                <?php if($is_karyawan): ?>
                if(data.reservasi_alert && data.reservasi_alert.length > 0) {
                    data.reservasi_alert.forEach(msg => {
                        const Toast = Swal.mixin({
                            toast: true, 
                            position: 'bottom-end', 
                            showConfirmButton: true, 
                            confirmButtonText: 'Oke',
                            confirmButtonColor: '#198754',
                            timer: 10000, 
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

            } catch (e) {
                console.error("Error parsing SSE data", e);
            }
        };
        
        globalEventSource.onerror = function(err) {
             console.log("SSE Connection lost, retrying...", err);
             if(statusDot) {
                statusDot.classList.remove('bg-success', 'bg-warning');
                statusDot.classList.add('bg-secondary');
            }
        };
    }

    // [PENTING] Jalankan saat halaman selesai dimuat (Standard)
    document.addEventListener('DOMContentLoaded', () => {
        initGlobalConnection();
    });
    
    // Fallback jika menggunakan Turbo/Hotwire di masa depan
    document.addEventListener('turbo:load', () => {
        if(globalEventSource) globalEventSource.close();
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