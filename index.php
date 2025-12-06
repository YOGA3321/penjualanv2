<?php
session_start();
// Halaman Public (Tanpa Login Wajib)
require_once 'auth/koneksi.php';

$is_logged_in = isset($_SESSION['user_id']);
$nama_user = $_SESSION['nama'] ?? 'Tamu';
$level_user = $_SESSION['level'] ?? '';

// --- LOGIKA DATA DARI DB ---
// 1. QUERY MENU FAVORIT (Acak 3 menu aktif)
$q_best = $koneksi->query("SELECT * FROM menu WHERE is_active = 1 ORDER BY RAND() LIMIT 3");

// 2. QUERY MENU PROMO (Harga < 25rb atau flag promo)
$q_promo = $koneksi->query("SELECT * FROM menu WHERE is_active = 1 AND (harga < 25000 OR is_promo = 1) LIMIT 3");

// 3. QUERY CABANG
$q_cabang = $koneksi->query("SELECT * FROM cabang ORDER BY is_pusat DESC, id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waroeng Modern Bites - Future Dining</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="shortcut icon" href="assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">

    <style>
        :root {
            --primary: #6366f1; /* Indigo-500 */
            --primary-hover: #4f46e5; /* Indigo-600 */
            --secondary: #ec4899; /* Pink-500 */
            --dark-bg: #0f172a; /* Slate-900 */
            --dark-bg-soft: #1e293b; /* Slate-800 */
            --text-light: #cbd5e1; /* Slate-300 */
            --white: #ffffff;
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
            --nav-height: 80px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: var(--font-body);
            color: var(--dark-bg);
            background-color: #f8fafc;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5 { font-family: var(--font-display); }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }

        /* Container & Grid */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .row { display: flex; flex-wrap: wrap; gap: 40px; }
        .col-half { flex: 1; min-width: 300px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }

        /* Helpers */
        .text-center { text-align: center; }
        .relative { position: relative; }
        .absolute { position: absolute; }
        .hidden { display: none; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-2 { gap: 10px; }
        .gap-4 { gap: 20px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        .mb-6 { margin-bottom: 30px; }
        .pt-20 { padding-top: 80px; }
        .py-24 { padding: 100px 0; }
        
        /* Typography */
        .display-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--white);
            margin-bottom: 25px;
        }
        .text-gradient {
            background: linear-gradient(to right, #818cf8, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-white { color: var(--white); }
        .text-light { color: var(--text-light); }
        .text-primary { color: var(--primary); }
        .font-bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }
        .tracking-wide { letter-spacing: 1px; }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0; left: 0; width: 100%;
            padding: 20px 0;
            z-index: 1000;
            transition: all 0.4s ease;
        }
        .glass-nav {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .nav-links { display: flex; gap: 30px; }
        .nav-link { color: var(--text-light); font-weight: 500; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { color: var(--white); }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.5);
        }
        .btn-outline {
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(5px);
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.6);
        }

        /* Hero Section */
        #home {
            min-height: 100vh;
            background: var(--dark-bg);
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            padding-top: 100px;
        }
        .bg-grid-pattern {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Badge */
        .badge-container {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 16px; 
            background: rgba(99,102,241,0.1); 
            border: 1px solid rgba(99,102,241,0.3); 
            border-radius: 50px; 
            margin-bottom: 2rem;
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        @keyframes blob {
            0% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0, 0) scale(1); }
        }
        @keyframes pulse-custom {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-blob { animation: blob 10s infinite; }
        .animate-pulse { animation: pulse-custom 2s infinite; }

        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(60px); opacity: 0.3;
        }
        .blob-1 { top: 0; left: -100px; width: 400px; height: 400px; background: var(--secondary); }
        .blob-2 { top: 0; right: 0; width: 500px; height: 500px; background: var(--primary); animation-delay: 2s; }
        .blob-3 { bottom: -100px; left: 30%; width: 400px; height: 400px; background: #ec4899; animation-delay: 4s; }

        .image-glow {
            position: absolute; inset: 0;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            filter: blur(60px); opacity: 0.4; border-radius: 50%;
        }
        .hero-img-wrapper {
            position: relative;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.1);
            padding: 10px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(5px);
            max-width: 500px; height: auto;
            margin: 0 auto;
        }
        .hero-img {
            width: 100%; border-radius: 50%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            transition: transform 0.5s;
        }
        
        .wave-bottom {
            position: absolute; bottom: 0; left: 0; width: 100%;
            line-height: 0; transform: rotate(180deg);
        }
        .wave-top {
            position: absolute; top: 0; left: 0; width: 100%;
            line-height: 0; transform: translateY(-98%);
        }
        .wave-svg { width: calc(100% + 1.3px); height: 80px; display: block; }
        
        .stat-box { text-align: left; }
        .stat-num { font-size: 1.8rem; font-weight: 700; color: white; }
        .stat-label { font-size: 0.75rem; letter-spacing: 1px; color: var(--text-light); text-transform: uppercase; }
        .vl { width: 1px; height: 40px; background: rgba(255,255,255,0.2); margin: 0 20px; }

        /* Menu Cards */
        .menu-card {
            background: white; border-radius: 20px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
            height: 100%;
        }
        .menu-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .card-img { height: 220px; position: relative; overflow: hidden; }
        .card-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .menu-card:hover img { transform: scale(1.1); }
        .price-badge {
            position: absolute; top: 15px; right: 15px;
            background: rgba(255,255,255,0.9);
            padding: 5px 15px; border-radius: 20px;
            color: var(--primary); font-weight: 700; font-size: 0.9rem;
        }
        .card-body { padding: 25px; }
        
        /* Custom Scroll for Branch List */
        .branch-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .branch-list::-webkit-scrollbar { width: 6px; }
        .branch-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .branch-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: 0.3s;
        }
        .branch-item:hover { background: #f1f5f9; transform: translateX(5px); }

        footer {
            background: var(--dark-bg);
            color: var(--text-light);
            padding-top: 100px; padding-bottom: 50px;
            border-top: 1px solid var(--dark-bg-soft);
            position: relative;
        }
        
        @media (max-width: 768px) {
            .display-title { font-size: 2.5rem; text-align: center; }
            .nav-links { display: none; }
            .row { flex-direction: column; }
            .hero-img-wrapper { max-width: 280px; margin: 30px auto; }
            .vl { display: none; }
            .stats-container { justify-content: center; display: flex; width: 100%; gap: 20px; flex-wrap: wrap; }
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <div class="container flex items-center justify-between">
            <a href="#" class="flex items-center gap-2">
                <img src="assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo" style="height: 40px; filter: brightness(0) invert(1);">
                <span style="font-family: var(--font-display); font-weight: 700; font-size: 1.25rem; color: white;">Modern Bites</span>
            </a>

            <ul class="nav-links">
                <li><a href="#home" class="nav-link active">Beranda</a></li>
                <li><a href="#about" class="nav-link">Tentang</a></li>
                <?php if($q_promo->num_rows > 0): ?><li><a href="#promo" class="nav-link">Promo</a></li><?php endif; ?>
                <li><a href="#menu" class="nav-link">Menu</a></li>
                <li><a href="#contact" class="nav-link">Lokasi</a></li>
            </ul>

            <div class="hidden md:block">
            <?php if ($is_logged_in): ?>
                <a href="<?= ($level_user=='pelanggan') ? 'pelanggan/index.php' : 'admin/index.php' ?>" class="btn btn-primary">
                    <?= ($level_user=='pelanggan') ? '<i class="fas fa-utensils"></i> Pesan' : '<i class="fas fa-tachometer-alt"></i> Dashboard' ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Masuk</a>
            <?php endif; ?>
            </div>
        </div>
    </nav>

    <section id="home">
        <div class="bg-grid-pattern"></div>
        <div class="blob blob-1 animate-blob"></div>
        <div class="blob blob-2 animate-blob"></div>
        <div class="blob blob-3 animate-blob"></div>

        <div class="container relative" style="z-index: 2;">
            <div class="row items-center">
                <div class="col-half" data-aos="fade-right">
                    <div class="badge-container">
                        <span style="width: 8px; height: 8px; background: #818cf8; border-radius: 50%;" class="animate-pulse"></span>
                        <span style="color: #a5b4fc; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">THE FUTURE OF DINING</span>
                    </div>

                    <h1 class="display-title">
                        Rasakan <br> <span class="text-gradient">Kenikmatan</span> <br> Tiada Dua.
                    </h1>
                    <p class="text-light mb-6" style="font-size: 1.1rem; line-height: 1.6; max-width: 90%;">
                        Nikmati hidangan premium dengan bahan pilihan dalam suasana yang nyaman dan modern. Pesan sekarang dan rasakan bedanya.
                    </p>

                    <div class="flex gap-4 mb-6">
                        <a href="#menu" class="btn btn-primary">Lihat Menu</a>
                        <a href="pelanggan/index.php" class="btn btn-outline">Reservasi</a>
                    </div>

                    <div class="flex items-center stats-container" style="margin-top: 3rem;">
                        <div class="stat-box">
                            <h4 class="stat-num">5k+</h4>
                            <p class="stat-label">Pelanggan</p>
                        </div>
                        <div class="vl"></div>
                        <div class="stat-box">
                            <h4 class="stat-num">4.9</h4>
                            <p class="stat-label">Rating</p>
                        </div>
                        <div class="vl"></div>
                        <div class="stat-box">
                            <h4 class="stat-num">50+</h4>
                            <p class="stat-label">Menu</p>
                        </div>
                    </div>
                </div>

                <div class="col-half" data-aos="fade-left">
                    <div class="relative animate-float">
                        <div class="image-glow"></div>
                        <div class="hero-img-wrapper">
                            <img src="assets/images/menu3.jpg" alt="Hero Burger" class="hero-img">
                        </div>
                        
                        <div style="position: absolute; bottom: -20px; right: 20px; background: rgba(255,255,255,0.95); padding: 15px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); animation: float 4s infinite reverse;">
                            <div class="flex items-center gap-2">
                                <div style="background: #ffedd5; padding: 8px; border-radius: 50%; color: #f97316;">
                                    <i class="fas fa-fire"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #64748b;">BEST SELLER</div>
                                    <div style="font-weight: 700; color: #0f172a;">Royal Burger</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wave-bottom">
            <svg class="wave-svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#ffffff"></path>
            </svg>
        </div>
    </section>

    <section id="about" class="py-24">
        <div class="container">
            <div class="row items-center">
                <div class="col-half" data-aos="fade-right">
                    <img src="assets/images/about-us.jpg" alt="About" style="width: 100%; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                </div>
                <div class="col-half" data-aos="fade-left">
                    <h5 class="text-primary font-bold uppercase tracking-wide mb-2">Tentang Kami</h5>
                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 20px;">Lebih Dari Sekadar Makanan.</h2>
                    <p style="color: #64748b; line-height: 1.8; margin-bottom: 25px;">
                        Kami percaya bahwa makanan adalah seni. Setiap hidangan di Waroeng Modern Bites dibuat dengan ketelitian tinggi, menggunakan bahan-bahan lokal terbaik yang dipilih langsung setiap pagi.
                    </p>
                    <ul class="mb-6">
                        <li class="flex items-center gap-2 mb-2">
                            <i class="fas fa-check-circle text-primary"></i> <span>Bahan organik dan segar</span>
                        </li>
                        <li class="flex items-center gap-2 mb-2">
                            <i class="fas fa-check-circle text-primary"></i> <span>Koki profesional (Ex-Hotel Bintang 5)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php if($q_promo->num_rows > 0): ?>
    <section id="promo" style="background: #fff1f2; padding: 100px 0;">
        <div class="container">
            <div class="text-center mb-6" data-aos="fade-up">
                <h5 style="color: #e11d48;" class="font-bold uppercase">HOT DEALS 🔥</h5>
                <h2 style="font-size: 2.5rem; font-weight: 800;">Promo Spesial Hari Ini</h2>
            </div>
            <div class="grid-3">
                <?php while($p = $q_promo->fetch_assoc()): ?>
                <div class="menu-card" data-aos="fade-up">
                    <div class="card-img">
                        <img src="<?= $p['gambar'] ? $p['gambar'] : 'assets/images/menu1.jpg' ?>" alt="<?= $p['nama_menu'] ?>">
                        <div class="price-badge" style="background: #e11d48; color: white;">Rp <?= number_format($p['harga'],0,',','.') ?></div>
                    </div>
                    <div class="card-body">
                        <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 10px;"><?= $p['nama_menu'] ?></h4>
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;"><?= substr($p['deskripsi'], 0, 50) ?>...</p>
                        <a href="penjualan/index.php" class="btn" style="background: #e11d48; color: white; width:100%; text-align:center;">Ambil Promo</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="menu" style="background: #f1f5f9; padding: 100px 0;">
        <div class="container">
            <div class="text-center mb-6" data-aos="fade-up">
                <h5 class="text-primary font-bold uppercase">Menu Favorit</h5>
                <h2 style="font-size: 2.5rem; font-weight: 800;">Pilihan Terbaik Minggu Ini</h2>
            </div>
            
            <div class="grid-3">
                <?php while($m = $q_best->fetch_assoc()): ?>
                <div class="menu-card" data-aos="fade-up">
                    <div class="card-img">
                        <img src="<?= $m['gambar'] ? $m['gambar'] : 'assets/images/menu2.jpg' ?>" alt="<?= $m['nama_menu'] ?>">
                        <div class="price-badge">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                    </div>
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-2">
                            <h4 style="font-size: 1.25rem; font-weight: 700;"><?= $m['nama_menu'] ?></h4>
                            <div style="color: #f59e0b; font-size: 0.8rem;"><i class="fas fa-star"></i> 5.0</div>
                        </div>
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;"><?= substr($m['deskripsi'], 0, 60) ?>...</p>
                        <a href="penjualan/index.php" style="display: block; text-align: center; padding: 10px; border: 2px solid var(--primary); color: var(--primary); border-radius: 10px; font-weight: 600;">Pesan Sekarang</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="text-center" style="margin-top: 50px;">
                <a href="penjualan/index.php" class="btn btn-primary">Lihat Menu Lengkap</a>
            </div>
        </div>
    </section>

    <section id="contact" style="padding: 100px 0; background: white;">
        <div class="container">
            <div class="row items-center">
                <div class="col-half" data-aos="fade-right">
                    <h5 class="text-primary font-bold uppercase mb-2">Lokasi Kami</h5>
                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 20px;">Kunjungi Outlet Kami</h2>
                    <p style="color: #64748b; margin-bottom: 30px;">
                        Temukan kenyamanan di outlet terdekat Anda.
                    </p>
                    
                    <div class="branch-list">
                        <?php while($c = $q_cabang->fetch_assoc()): ?>
                        <div class="branch-item flex items-center gap-4">
                            <div style="width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <h5 style="font-weight: 700; display:flex; align-items:center; gap:10px;">
                                    <?= $c['nama_cabang'] ?>
                                    <?php if($c['is_pusat']): ?>
                                        <span style="font-size:0.6rem; background:var(--primary); color:white; padding:2px 8px; border-radius:20px;">PUSAT</span>
                                    <?php endif; ?>
                                </h5>
                                <p style="font-size: 0.85rem; color: #64748b; margin-bottom:2px;"><?= $c['alamat'] ?></p>
                                <div style="font-size: 0.8rem; color: #94a3b8;">
                                    <i class="far fa-clock"></i> <?= date('H:i', strtotime($c['jam_buka'])) ?> - <?= date('H:i', strtotime($c['jam_tutup'])) ?>
                                    <span style="margin-left:10px; color: <?= $c['is_open']?'#10b981':'#ef4444' ?>; font-weight:bold;">
                                        <?= $c['is_open'] ? 'BUKA' : 'TUTUP' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                </div>
                <div class="col-half" data-aos="fade-left">
                    <div style="border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); height: 450px;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31662.151250121915!2d112.72093057720754!3d-7.267118852885301!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fbc332a3fb01%3A0xc2c2e7e4b44f1d6!2sPagi%20Sore%20Surabaya%20%40Embong%20Sawo!5e0!3m2!1sid!2sid!4v1765002598839!5m2!1sid!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wave-top">
            <svg class="wave-svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#ffffff" transform="rotate(180 600 60)"></path>
            </svg>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-half">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo" style="height: 30px; filter: brightness(0) invert(1); opacity: 0.8;">
                        <span style="font-size: 1.2rem; font-weight: 700; color: white;">Modern Bites</span>
                    </div>
                    <p style="max-width: 300px; font-size: 0.9rem; opacity: 0.7;">
                        Mengubah cara Anda menikmati makanan dengan standar kualitas tertinggi dan inovasi tanpa henti.
                    </p>
                </div>
                <div class="col-half text-center">
                    <h5 style="color: white; font-weight: 700; margin-bottom: 20px;">Social Media</h5>
                    <div class="flex justify-center gap-4">
                        <a href="#" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; border-radius: 50%; color: white;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; border-radius: 50%; color: white;"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 40px; padding-top: 20px; text-align: center; font-size: 0.85rem; opacity: 0.5;">
                © <?= date('Y') ?> Waroeng Modern Bites. Created by Lopyta.
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        // Navbar Scroll Effect
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('glass-nav');
            } else {
                navbar.classList.remove('glass-nav');
            }
        });

        // Scrollspy
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (scrollY >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes(current)) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>