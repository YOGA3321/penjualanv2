<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Manajemen Menu - Waroeng Modern Bites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css"> 
    <link rel="shortcut icon" href="../assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">
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
                <li class="active"><a href="admin_menu.html"><i class="fas fa-utensils fa-fw"></i> <span>Manajemen Menu</span></a></li>
                <li><a href="#"><i class="fas fa-tags fa-fw"></i> <span>Kategori Menu</span></a></li>
            </ul>
            <p class="sidebar-heading">Pengelolaan</p>
            <ul>
                <li><a href="#"><i class="fas fa-users fa-fw"></i> <span>Manajemen User</span></a></li>
                <li><a href="#"><i class="fas fa-store fa-fw"></i> <span>Manajemen Cabang</span></a></li>
                <li><a href="#"><i class="fas fa-chair fa-fw"></i> <span>Manajemen Meja</span></a></li>
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
                    <h1>Manajemen Menu</h1>
                    <p>Tambah, ubah, atau hapus menu yang tersedia.</p>
                </div>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#menuModal">
                    <i class="fas fa-plus me-2"></i>Tambah Menu Baru
                </button>
            </div>
        </header>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Nama Menu</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td><img src="assets/images/menu1.jpg" alt="Menu" class="table-img"></td>
                                <td><strong>Lobster Balado</strong></td>
                                <td>Makanan Berat</td>
                                <td>Rp 125.000</td>
                                <td><span class="badge bg-success">Tersedia (50)</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td><img src="assets/images/menu2.jpg" alt="Menu" class="table-img"></td>
                                <td><strong>Kentang & Nuget</strong></td>
                                <td>Makanan Ringan</td>
                                <td>Rp 35.000</td>
                                <td><span class="badge bg-warning text-dark">Hampir Habis (5)</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td><img src="assets/images/menu3.jpg" alt="Menu" class="table-img"></td>
                                <td><strong>Kopi Susu Kekinian</strong></td>
                                <td>Minuman</td>
                                <td>Rp 25.000</td>
                                <td><span class="badge bg-danger">Habis (0)</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menuModalLabel">Tambah Menu Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="menuName" class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" id="menuName" required>
                    </div>
                    <div class="mb-3">
                        <label for="menuPhoto" class="form-label">Foto Menu</label>
                        <input class="form-control" type="file" id="menuPhoto">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="menuPrice" class="form-label">Harga</label>
                            <input type="number" class="form-control" id="menuPrice" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="menuStock" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="menuStock" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="menuCategory" class="form-label">Kategori</label>
                        <select class="form-select" id="menuCategory">
                            <option selected>Pilih Kategori...</option>
                            <option value="1">Makanan Berat</option>
                            <option value="2">Makanan Ringan</option>
                            <option value="3">Minuman</option>
                        </select>
                    </div>
                     <div class="mb-3">
                        <label for="menuDescription" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="menuDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary">Simpan Menu</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>