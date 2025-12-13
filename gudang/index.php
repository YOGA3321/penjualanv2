<?php
session_start();
// Allow Admin & Gudang
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'gudang' && $_SESSION['level'] != 'admin')) { 
    header("Location: ../login.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Dashboard Gudang";
$active_menu = "dashboard";

// --- WIDGET STATISTICS ---
$q_stok = $koneksi->query("SELECT COUNT(*) as total FROM gudang_items WHERE stok < 10");
$stok_menipis = $q_stok->fetch_assoc()['total'];

$q_pending = $koneksi->query("SELECT COUNT(*) as total FROM request_stok WHERE status='pending'");
$req_pending = $q_pending->fetch_assoc()['total'];

$q_dikirim = $koneksi->query("SELECT COUNT(*) as total FROM request_stok WHERE status='dikirim'");
$req_dikirim = $q_dikirim->fetch_assoc()['total'];

// Mutasi Minggu Ini (Chart Data)
$monday = date("Y-m-d", strtotime('monday this week'));
$sunday = date("Y-m-d", strtotime('sunday this week'));
$q_mutasi = $koneksi->query("SELECT DATE(created_at) as tgl, 
                             SUM(CASE WHEN jenis_mutasi LIKE 'masuk%' THEN 1 ELSE 0 END) as masuk,
                             SUM(CASE WHEN jenis_mutasi LIKE 'keluar%' THEN 1 ELSE 0 END) as keluar
                             FROM mutasi_gudang 
                             WHERE DATE(created_at) BETWEEN '$monday' AND '$sunday'
                             GROUP BY DATE(created_at)");
$chart_labels = [];
$chart_masuk = [];
$chart_keluar = [];
while($r = $q_mutasi->fetch_assoc()) {
    $chart_labels[] = date('d M', strtotime($r['tgl']));
    $chart_masuk[] = $r['masuk'];
    $chart_keluar[] = $r['keluar'];
}

include '../layouts/admin/header.php';
?>

<div class="alert alert-primary shadow-sm border-0 mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h4 class="alert-heading fw-bold"><i class="fas fa-warehouse me-2"></i>Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>
        <p class="mb-0">Selamat datang di <strong>Central Warehouse Management System</strong>.</p>
    </div>
    <div id="sse-status" class="badge bg-white text-primary"><i class="fas fa-wifi me-1"></i> Live Connection</div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Stok Menipis</p>
                        <h4 class="fw-bold text-warning mb-0"><?= $stok_menipis ?> Item</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body text-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Permintaan Baru</p>
                        <h4 class="fw-bold mb-0" id="count-pending"><?= $req_pending ?> Request</h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger"><i class="fas fa-inbox fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Sedang Dikirim</p>
                        <h4 class="fw-bold text-info mb-0"><?= $req_dikirim ?> Pengiriman</h4>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info"><i class="fas fa-truck fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="inventory.php" class="btn btn-outline-primary w-100 py-3 fw-bold shadow-sm h-100 d-flex flex-column align-items-center justify-content-center gap-2">
            <i class="fas fa-boxes fa-2x"></i> Manajemen Stok
        </a>
    </div>
    <div class="col-md-3">
        <a href="barang_masuk.php" class="btn btn-outline-success w-100 py-3 fw-bold shadow-sm h-100 d-flex flex-column align-items-center justify-content-center gap-2">
            <i class="fas fa-download fa-2x"></i> Barang Masuk
        </a>
    </div>
    <div class="col-md-3">
        <a href="permintaan_masuk.php" class="btn btn-outline-danger w-100 py-3 fw-bold shadow-sm h-100 d-flex flex-column align-items-center justify-content-center gap-2">
            <i class="fas fa-dolly-flatbed fa-2x"></i> Permintaan Cabang
        </a>
    </div>
    <div class="col-md-3">
        <a href="pemasok.php" class="btn btn-outline-secondary w-100 py-3 fw-bold shadow-sm h-100 d-flex flex-column align-items-center justify-content-center gap-2">
            <i class="fas fa-truck fa-2x"></i> Data Pemasok
        </a>
    </div>
</div>

<!-- Chart Section -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="fas fa-chart-bar me-2"></i>Aktivitas Gudang Minggu Ini</div>
    <div class="card-body">
        <canvas id="mutasiChart" height="80"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// 1. Chart
const ctx = document.getElementById('mutasiChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            { label: 'Barang Masuk', data: <?= json_encode($chart_masuk) ?>, backgroundColor: '#198754' },
            { label: 'Barang Keluar', data: <?= json_encode($chart_keluar) ?>, backgroundColor: '#dc3545' }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// 2. SSE Notifications
if(typeof(EventSource) !== "undefined") {
const source = new EventSource("api/sse_channel.php");
    
    source.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        // 1. Update System Live (Online Users) - HANDLED BY FOOTER GLOBAL SSE
        // if(data.online_users !== undefined) {
        //      const el = document.getElementById('onlineCount');
        //      if(el) el.innerText = data.online_users;
        // }

        // 2. Update Request Pending Counter
        if(data.pending_requests !== undefined) {
            const elPending = document.getElementById("count-pending");
            if(elPending) elPending.innerText = data.pending_requests + " Request";
        }

        // 3. Notifikasi Request Baru (PENTING!)
        if(data.new_request_alert) {
             Swal.fire({
                title: data.new_request_alert.title,
                text: data.new_request_alert.message,
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 6000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            // Optional: Play notification sound
            // new Audio('../assets/audio/notification.mp3').play().catch(e=>{});
        }
    };

    source.onerror = function() {
        document.getElementById("sse-status").className = "badge bg-warning text-dark";
        document.getElementById("sse-status").innerText = "Reconnecting...";
    };

    source.onopen = function() {
        document.getElementById("sse-status").className = "badge bg-success";
        document.getElementById("sse-status").innerText = "Live";
    };
} else {
    console.log("Browser does not support SSE.");
}
</script>

<?php include '../layouts/admin/footer.php'; ?>
