<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Manajemen Meja - Waroeng Modern Bites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="shortcut icon" href="assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar d-none d-lg-flex">
        <div class="sidebar-header">
             <a href="index.html" class="sidebar-logo">
                <img src="assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo">
                <span>Modern Bites</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <p class="sidebar-heading">Menu Utama</p>
            <ul>
                <li><a href="#"><i class="fas fa-chart-line fa-fw"></i> <span>Laporan</span></a></li>
                <li><a href="admin_menu.html"><i class="fas fa-utensils fa-fw"></i> <span>Manajemen Menu</span></a></li>
                <li><a href="#"><i class="fas fa-tags fa-fw"></i> <span>Kategori Menu</span></a></li>
            </ul>
            <p class="sidebar-heading">Pengelolaan</p>
            <ul>
                <li><a href="admin_users.html"><i class="fas fa-users fa-fw"></i> <span>Manajemen User</span></a></li>
                <li><a href="admin_cabang.html"><i class="fas fa-store fa-fw"></i> <span>Manajemen Cabang</span></a></li>
                <li class="active"><a href="admin_meja.html"><i class="fas fa-chair fa-fw"></i> <span>Manajemen Meja</span></a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="index.html"><i class="fas fa-sign-out-alt fa-fw"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                 <button class="btn d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>Manajemen Meja</h1>
                    <p>Kelola meja yang tersedia di setiap cabang.</p>
                </div>
            </div>
             <div class="header-right">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mejaModal">
                    <i class="fas fa-plus me-2"></i>Tambah Meja Baru
                </button>
            </div>
        </header>

        <div class="card mb-4">
            <div class="card-body d-flex align-items-center">
                 <label for="filterCabang" class="form-label me-3 mb-0">Pilih Cabang:</label>
                 <select class="form-select w-auto" id="filterCabang">
                    <option selected>Jakarta Pusat</option>
                    <option value="1">Surabaya Timur</option>
                    <option value="2">Bandung Kota</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="card table-card text-center">
                    <div class="card-body">
                        <i class="fas fa-chair table-icon text-success"></i>
                        <h5 class="card-title mt-3">Meja 01</h5>
                        <p class="mb-0"><span class="badge bg-success">Tersedia</span></p>
                    </div>
                    <div class="card-footer bg-white border-0">
                         <button class="btn btn-sm btn-outline-secondary" title="Cetak QR Code"><i class="fas fa-qrcode"></i></button>
                         <button class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></button>
                         <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
             <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="card table-card text-center">
                    <div class="card-body">
                         <i class="fas fa-chair table-icon text-danger"></i>
                        <h5 class="card-title mt-3">Meja 02</h5>
                         <p class="mb-0"><span class="badge bg-danger">Terisi</span></p>
                    </div>
                    <div class="card-footer bg-white border-0">
                         <button class="btn btn-sm btn-outline-secondary" title="Cetak QR Code"><i class="fas fa-qrcode"></i></button>
                         <button class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></button>
                         <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>


<div class="modal fade" id="mejaModal" tabindex="-1" aria-labelledby="mejaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mejaModalLabel">Tambah Meja Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="mejaNumber" class="form-label">Nomor Meja</label>
                        <input type="number" class="form-control" id="mejaNumber" placeholder="Contoh: 15" required>
                    </div>
                     <div class="mb-3">
                        <label for="mejaCabang" class="form-label">Lokasi Cabang</label>
                        <select class="form-select" id="mejaCabang">
                            <option selected>Jakarta Pusat</option>
                            <option value="1">Surabaya Timur</option>
                            <option value="2">Bandung Kota</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary">Simpan Meja</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>