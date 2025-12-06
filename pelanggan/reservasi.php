<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'pelanggan') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

// [AJAX] GET JAM OPERASIONAL
if(isset($_GET['get_jam']) && isset($_GET['cabang_id'])) {
    $cid = $_GET['cabang_id'];
    $date = $_GET['tanggal']; // Y-m-d
    
    $c = $koneksi->query("SELECT jam_buka, jam_tutup, is_open FROM cabang WHERE id = '$cid'")->fetch_assoc();
    
    $options = '<option value="" selected disabled>-- Pilih Jam --</option>';
    
    if ($c['is_open'] == 0) {
        echo '<option value="" disabled>Toko Tutup Sementara</option>';
    } else {
        $start = strtotime($c['jam_buka']);
        $end = strtotime($c['jam_tutup']);
        
        // Cek apakah tanggal booking hari ini?
        $is_today = ($date == date('Y-m-d'));
        $now = time();
        
        for ($i = $start; $i < $end; $i += 1800) { // Interval 30 menit
            $jam_str = date('H:i', $i);
            $time_val = strtotime("$date $jam_str");
            
            // Validasi: Tidak boleh booking jam yang sudah lewat (jika hari ini)
            // Dan minimal 30 menit dari sekarang
            $disabled = '';
            $label = $jam_str;
            
            if ($is_today && $time_val < ($now + 1800)) {
                $disabled = 'disabled';
                $label .= ' (Tutup/Lewat)';
            }
            
            $options .= "<option value='$jam_str' $disabled>$label</option>";
        }
        echo $options;
    }
    exit; 
}

// PROSES SIMPAN (Sama seperti sebelumnya)
if(isset($_POST['submit_reservasi'])) {
    $uid = $_SESSION['user_id'];
    $meja_id = $_POST['meja_id'];
    $tgl = $_POST['tanggal']; 
    $jam = $_POST['jam'];     
    $waktu_booking = "$tgl $jam:00";
    $durasi = 45;
    
    $book_time = strtotime($waktu_booking);
    $current_time = time();
    
    if ($book_time < ($current_time + 1800)) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Minimal 30 menit sebelum kedatangan!'];
    } else {
        // Cek Bentrok
        $start_time = $waktu_booking;
        $end_time = date('Y-m-d H:i:s', strtotime($start_time) + ($durasi * 60));
        
        $cek = $koneksi->query("SELECT id FROM reservasi WHERE meja_id='$meja_id' AND status IN ('pending','checkin') AND (('$start_time' < DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)) AND ('$end_time' > waktu_reservasi))");
        
        if($cek->num_rows > 0) {
            $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Penuh', 'text'=>'Meja sudah dipesan jam segitu.'];
        } else {
            $uuid = uniqid('RES-');
            $stmt = $koneksi->prepare("INSERT INTO reservasi (uuid, user_id, meja_id, waktu_reservasi, durasi_menit) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siisi", $uuid, $uid, $meja_id, $waktu_booking, $durasi);
            if($stmt->execute()) $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>'Reservasi sukses!'];
            header("Location: index.php"); exit;
        }
    }
    header("Location: reservasi.php"); exit;
}

$cabangs = $koneksi->query("SELECT * FROM cabang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Reservasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .card-form { border-radius: 20px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 500px;">
    <a href="index.php" class="text-decoration-none text-muted mb-3 d-block"><i class="fas fa-arrow-left"></i> Kembali</a>
    
    <div class="card card-form p-4 bg-white">
        <h4 class="fw-bold text-primary mb-4"><i class="fas fa-calendar-plus me-2"></i>Reservasi Meja</h4>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Pilih Cabang</label>
                <select id="cabang_id" name="cabang_id" class="form-select" onchange="refreshData()" required>
                    <option value="" selected disabled>-- Pilih Lokasi --</option>
                    <?php while($c = $cabangs->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" min="<?= date('Y-m-d') ?>" onchange="refreshData()" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Jam Kedatangan</label>
                <select name="jam" id="jam_select" class="form-select" disabled required>
                    <option value="">-- Pilih Cabang & Tanggal Dulu --</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-muted">Pilih Meja</label>
                <select name="meja_id" id="meja_id" class="form-select" disabled required>
                    <option value="">-- Menunggu Jam --</option>
                </select>
            </div>

            <button type="submit" name="submit_reservasi" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Booking Sekarang</button>
        </form>
    </div>
</div>

<?php 
// Preload Meja
$all_meja = [];
$q = $koneksi->query("SELECT id, cabang_id, nomor_meja, status FROM meja ORDER BY CAST(nomor_meja AS UNSIGNED) ASC");
while($m = $q->fetch_assoc()) $all_meja[] = $m;
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const dbMeja = <?= json_encode($all_meja) ?>;

    function refreshData() {
        const cabangId = document.getElementById('cabang_id').value;
        const tanggal = document.getElementById('tanggal').value;
        
        if(cabangId && tanggal) {
            loadJam(cabangId, tanggal);
            loadMeja(cabangId);
        }
    }

    function loadMeja(cabangId) {
        const select = document.getElementById('meja_id');
        select.innerHTML = '<option value="" selected disabled>-- Pilih Meja --</option>';
        select.disabled = false;

        const filtered = dbMeja.filter(m => m.cabang_id == cabangId);
        if(filtered.length === 0) select.innerHTML = '<option value="" disabled>Tidak ada meja</option>';
        else {
            filtered.forEach(m => {
                let statusTxt = m.status === 'terisi' ? ' (Dipakai Makan)' : '';
                let style = m.status === 'terisi' ? 'color:red;' : '';
                select.innerHTML += `<option value="${m.id}" style="${style}">Meja ${m.nomor_meja}${statusTxt}</option>`;
            });
        }
    }

    function loadJam(cabangId, tanggal) {
        const select = document.getElementById('jam_select');
        select.disabled = true; select.innerHTML = '<option>Loading...</option>';
        
        fetch(`reservasi.php?get_jam=1&cabang_id=${cabangId}&tanggal=${tanggal}`)
        .then(r => r.text())
        .then(html => { select.innerHTML = html; select.disabled = false; });
    }
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>Swal.fire({icon: '<?= $_SESSION['swal']['icon'] ?>', title: '<?= $_SESSION['swal']['title'] ?>', text: '<?= $_SESSION['swal']['text'] ?>'});</script>
<?php unset($_SESSION['swal']); endif; ?>

</body>
</html>