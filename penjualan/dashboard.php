<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Waroeng Modern Bites</title>
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
                <ul>
                    <li class="active"><a href="#"><i class="fas fa-th-large fa-fw"></i> <span>Dashboard</span></a></li>
                    <li><a href="#"><i class="fas fa-receipt fa-fw"></i> <span>Riwayat Pesanan</span></a></li>
                    <li><a href="#"><i class="fas fa-user-circle fa-fw"></i> <span>Profil Saya</span></a></li>
                    <li><a href="#"><i class="fas fa-qrcode fa-fw"></i> <span>Scan Meja</span></a></li>
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
                        <h1>Selamat Datang, Yoga!</h1>
                        <p class="d-none d-md-block">Pilih menu favoritmu di bawah ini.</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="search-box d-none d-md-block">
                        <input type="text" placeholder="Cari menu...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="vr mx-3 d-none d-md-block"></div>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://i.pravatar.cc/150?u=yoga" alt="User" class="rounded-circle" width="40" height="40">
                            <div class="ms-2 d-none d-md-block">
                                <strong class="d-block">YOGA_46</strong>
                                <small class="text-muted">Pelanggan</small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="category-filter">
                <button class="filter-btn active">Semua</button>
                <button class="filter-btn">Makanan Berat</button>
                <button class="filter-btn">Makanan Ringan</button>
                <button class="filter-btn">Minuman</button>
            </div>

            <div class="menu-grid">
                <div class="menu-card" data-id="1" data-name="Lobster Balado Khas Bali" data-price="125000" data-image="assets/images/menu1.jpg">
                    <img src="assets/images/menu1.jpg" alt="Lobster Balado" class="menu-card-img">
                    <div class="menu-card-body">
                        <h5>Lobster Balado Khas Bali</h5>
                        <p class="price">Rp 125.000</p>
                    </div>
                    <button class="btn-add-to-cart">+</button>
                </div>
                <div class="menu-card" data-id="2" data-name="Kentang & Nuget" data-price="35000" data-image="assets/images/menu2.jpg">
                    <img src="assets/images/menu2.jpg" alt="Kentang & Nuget" class="menu-card-img">
                    <div class="menu-card-body">
                        <h5>Kentang & Nuget</h5>
                        <p class="price">Rp 35.000</p>
                    </div>
                    <button class="btn-add-to-cart">+</button>
                </div>
                <div class="menu-card" data-id="3" data-name="Salad Segar & Sehat" data-price="45000" data-image="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=1780&auto=format&fit=crop">
                    <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=1780&auto=format&fit=crop" alt="Salad Segar" class="menu-card-img">
                    <div class="menu-card-body">
                        <h5>Salad Segar & Sehat</h5>
                        <p class="price">Rp 45.000</p>
                    </div>
                    <button class="btn-add-to-cart">+</button>
                </div>
                <div class="menu-card" data-id="4" data-name="Kopi Susu Kekinian" data-price="25000" data-image="assets/images/menu3.jpg">
                    <img src="assets/images/menu3.jpg" alt="Kopi Susu" class="menu-card-img">
                    <div class="menu-card-body">
                        <h5>Kopi Susu Kekinian</h5>
                        <p class="price">Rp 25.000</p>
                    </div>
                    <button class="btn-add-to-cart">+</button>
                </div>
            </div>
        </main>
    </div>

    <div class="cart-fab" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-controls="cartOffcanvas">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge">0</span>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="cartOffcanvasLabel">Keranjang Saya</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cart-items-container">
                <p class="text-center text-muted empty-cart-message">Keranjangmu masih kosong!</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total Pesanan:</span>
                <strong id="cart-total-price">Rp 0</strong>
            </div>
            <button class="btn-checkout">Lanjutkan ke Pembayaran</button>
        </div>
    </div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header">
             <a href="index.html" class="sidebar-logo">
                <img src="assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo">
                <span>Modern Bites</span>
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
             <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="#"><i class="fas fa-th-large fa-fw"></i> <span>Dashboard</span></a></li>
                    <li><a href="#"><i class="fas fa-receipt fa-fw"></i> <span>Riwayat Pesanan</span></a></li>
                    <li><a href="#"><i class="fas fa-user-circle fa-fw"></i> <span>Profil Saya</span></a></li>
                    <li><a href="#"><i class="fas fa-qrcode fa-fw"></i> <span>Scan Meja</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="index.html"><i class="fas fa-sign-out-alt fa-fw"></i> <span>Logout</span></a>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>