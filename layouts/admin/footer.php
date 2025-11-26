</main> 
</div> 

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu Navigasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="laporan">Laporan</a></li>
                <li><a href="menu">Manajemen Menu</a></li>
                <li><a href="kategori">Kategori Menu</a></li>
                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <li><a href="users">Manajemen User</a></li>
                    <li><a href="cabang">Manajemen Cabang</a></li>
                <?php endif; ?>
                <li><a href="meja">Manajemen Meja</a></li>
                <li><a href="../logout">Logout</a></li>
            </ul>
        </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Variabel Global
    let globalEventSource = null;

    function initGlobalConnection() {
        const statusDot = document.getElementById('statusIndicator');
        const onlineCount = document.getElementById('onlineCount');

        // Path relatif ke API (sesuaikan jika struktur folder berubah)
        // Kita asumsikan footer di-include dari folder admin/
        globalEventSource = new EventSource('api/sse_channel.php');

        globalEventSource.onopen = function() {
            // Ubah warna jadi HIJAU (Connected)
            if(statusDot) {
                statusDot.className = 'rounded-circle bg-success me-2';
                statusDot.title = "Connected Realtime";
                // Efek kedip
                statusDot.style.boxShadow = "0 0 5px #198754";
            }
        };

        globalEventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            // Update Jumlah User Online
            if(onlineCount) {
                onlineCount.innerText = data.online_users;
            }
        };

        globalEventSource.onerror = function() {
            // Ubah warna jadi MERAH (Disconnected)
            if(statusDot) {
                statusDot.className = 'rounded-circle bg-danger me-2';
                statusDot.title = "Connection Lost - Reconnecting...";
                statusDot.style.boxShadow = "none";
            }
        };
    }

    // Jalankan saat halaman load
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
        timer: 2000,
        showConfirmButton: false
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>

</body>
</html>