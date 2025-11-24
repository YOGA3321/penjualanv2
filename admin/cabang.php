<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Manajemen Cabang - Waroeng Modern Bites</title>
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
                <li class="active"><a href="admin_cabang.html"><i class="fas fa-store fa-fw"></i> <span>Manajemen Cabang</span></a></li>
                <li><a href="admin_meja.html"><i class="fas fa-chair fa-fw"></i> <span>Manajemen Meja</span></a></li>
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
                    <h1>Manajemen Cabang</h1>
                    <p>Tambah atau kelola data cabang restoran Anda.</p>
                </div>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cabangModal">
                    <i class="fas fa-plus me-2"></i>Tambah Cabang Baru
                </button>
            </div>
        </header>

        <div class="row">
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card branch-card">
                    <img src="https://images.unsplash.com/photo-1552590768-478647a56b33?q=80&w=1974&auto=format&fit=crop" class="card-img-top" alt="Jakarta">
                    <div class="card-body">
                        <h5 class="card-title">Jakarta Pusat</h5>
                        <p class="card-text text-muted">Jl. Sudirman No. 123, Jakarta</p>
                    </div>
                    <div class="card-footer bg-white border-0 text-end">
                        <button class="btn btn-sm btn-outline-primary">Edit</button>
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </div>
                </div>
            </div>
             <div class="col-md-6 col-lg-4 mb-4">
                <div class="card branch-card">
                    <img src="https://images.unsplash.com/photo-1583262602985-6902484c690c?q=80&w=2070&auto=format&fit=crop" class="card-img-top" alt="Surabaya">
                    <div class="card-body">
                        <h5 class="card-title">Surabaya Timur</h5>
                        <p class="card-text text-muted">Jl. Raya Gubeng No. 45, Surabaya</p>
                    </div>
                     <div class="card-footer bg-white border-0 text-end">
                        <button class="btn btn-sm btn-outline-primary">Edit</button>
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="cabangModal" tabindex="-1" aria-labelledby="cabangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cabangModalLabel">Tambah Cabang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="cabangName" class="form-label">Nama Cabang</label>
                        <input type="text" class="form-control" id="cabangName" placeholder="Contoh: Jakarta Pusat" required>
                    </div>
                    <div class="mb-3">
                        <label for="cabangAddress" class="form-label">Alamat</label>
                        <textarea class="form-control" id="cabangAddress" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cabangPhoto" class="form-label">Foto Cabang</label>
                        <input class="form-control" type="file" id="cabangPhoto">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary">Simpan Cabang</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>