<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Manajemen User - Waroeng Modern Bites</title>
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
                <li class="active"><a href="admin_users.html"><i class="fas fa-users fa-fw"></i> <span>Manajemen User</span></a></li>
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
                    <h1>Manajemen User</h1>
                    <p>Tambah, ubah, atau hapus data pengguna.</p>
                </div>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="fas fa-plus me-2"></i>Tambah User Baru
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
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Level</th>
                                <th>Tanggal Bergabung</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://i.pravatar.cc/150?u=admin" alt="User" class="table-img me-3">
                                        <strong>YOGA_46 (Admin)</strong>
                                    </div>
                                </td>
                                <td>admin@modernbites.com</td>
                                <td><span class="badge bg-danger">Admin</span></td>
                                <td>15 Okt 2025</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" disabled><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://i.pravatar.cc/150?u=karyawan1" alt="User" class="table-img me-3">
                                        <strong>Budi Santoso</strong>
                                    </div>
                                </td>
                                <td>budi.s@modernbites.com</td>
                                <td><span class="badge bg-info">Karyawan</span></td>
                                <td>14 Okt 2025</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                             <tr>
                                <td>3</td>
                                <td>
                                     <div class="d-flex align-items-center">
                                        <img src="https://i.pravatar.cc/150?u=pelanggan1" alt="User" class="table-img me-3">
                                        <strong>Siti Aminah</strong>
                                    </div>
                                </td>
                                <td>sitiaminah@gmail.com</td>
                                <td><span class="badge bg-secondary">Pelanggan</span></td>
                                <td>13 Okt 2025</td>
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

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="userName" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="userName" required>
                    </div>
                    <div class="mb-3">
                        <label for="userEmail" class="form-label">Alamat Email</label>
                        <input type="email" class="form-control" id="userEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="userPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="userPassword" placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>
                    <div class="mb-3">
                        <label for="userLevel" class="form-label">Level Pengguna</label>
                        <select class="form-select" id="userLevel">
                            <option value="pelanggan" selected>Pelanggan</option>
                            <option value="karyawan">Karyawan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary">Simpan User</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>