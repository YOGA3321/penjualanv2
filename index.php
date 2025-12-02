<?php
session_start();
// Halaman ini TIDAK BUTUH LOGIN (Public)
// Tapi kita cek session untuk mengubah tombol di Navbar

require_once 'auth/koneksi.php'; // Pastikan path ini benar

$is_logged_in = isset($_SESSION['user_id']);
$nama_user = $_SESSION['nama'] ?? 'Tamu';
$level_user = $_SESSION['level'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waroeng Modern Bites - Taste the Future</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/parallax/jarallax.css">
    
    <link rel="shortcut icon" href="assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">

    <style>
        :root {
            /* Palet Warna Modern */
            --primary-grad: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); /* Indigo to Purple */
            --secondary-grad: linear-gradient(135deg, #3b82f6 0%, #2dd4bf 100%); /* Blue to Teal */
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5 { font-family: 'Poppins', sans-serif; }

        /* --- NAVBAR GLASSMORPHISM --- */
        .navbar {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: var(--glass-border);
            padding: 15px 0;
            transition: all 0.4s ease;
        }
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 10px 0;
        }
        .nav-link {
            color: #334155 !important;
            font-weight: 500;
            margin: 0 10px;
            position: relative;
            transition: color 0.3s;
        }
        .nav-link:hover, .nav-link.active { color: #6366f1 !important; }
        .nav-link::after {
            content: ''; position: absolute; width: 0; height: 2px;
            bottom: 0; left: 0; background: var(--primary-grad);
            transition: width 0.3s;
        }
        .nav-link:hover::after { width: 100%; }

        /* --- TOMBOL MODERN --- */
        .btn-modern {
            background: var(--primary-grad);
            color: white; border: none;
            border-radius: 50px;
            padding: 12px 35px;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }
        .btn-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.5);
            color: white;
        }

        /* --- HERO SECTION UPGRADE --- */
        .hero-modern {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            /* Menggunakan gambar lama Anda sebagai background */
            background: url('assets/images/hero-bg.jpg') no-repeat center center/cover;
            overflow: hidden;
        }
        .hero-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to right, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.7) 100%);
        }
        .hero-content { position: relative; z-index: 2; }
        
        .hero-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            color: #818cf8;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .display-title {
            font-size: 4rem; font-weight: 800; line-height: 1.1;
            background: linear-gradient(to right, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        /* --- CARD MENU UPGRADE --- */
        .card-menu-modern {
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            height: 100%;
        }
        .card-menu-modern:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .card-img-wrapper {
            overflow: hidden; height: 220px;
        }
        .card-img-wrapper img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.5s ease;
        }
        .card-menu-modern:hover .card-img-wrapper img { transform: scale(1.1); }

        /* --- FOOTER MODERN --- */
        footer {
            background: #0f172a;
            color: #cbd5e1;
            padding-top: 80px;
            border-top: 5px solid #6366f1;
        }
        .social-btn {
            width: 45px; height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            display: flex; align-items: center; justify-content: center;
            color: white; transition: all 0.3s;
            text-decoration: none;
        }
        .social-btn:hover {
            background: var(--primary-grad);
            transform: rotate(360deg);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo" height="45">
                <span class="fw-bold" style="background: var(--primary-grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Modern Bites</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="#home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Kontak</a></li>
                    
                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                        <?php if ($is_logged_in): ?>
                            <?php if ($level_user == 'pelanggan'): ?>
                                <a href="penjualan/index.php" class="btn btn-modern">
                                    <i class="fas fa-utensils me-2"></i> Pesan Sekarang
                                </a>
                            <?php else: ?>
                                <a href="admin/index.php" class="btn btn-modern">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-modern px-4">
                                <i class="fas fa-sign-in-alt me-2"></i> Masuk
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-modern">
        <div class="hero-overlay"></div>
        <div class="container hero-content text-white">
            <div class="row align-items-center">
                <div class="col-lg-7" data-aos="fade-up" data-aos-duration="1000">
                    <span class="hero-badge">✨ Kuliner Masa Depan</span>
                    <h1 class="display-title">Nikmati Rasa<br>Yang Tak Terlupakan.</h1>
                    <p class="lead mb-5 opacity-75" style="max-width: 550px;">
                        Selamat datang di Waroeng Modern Bites. Kami menyajikan hidangan berkualitas dengan sentuhan teknologi untuk pengalaman bersantap yang lebih baik.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#menu" class="btn btn-modern btn-lg">Lihat Menu Kami</a>
                        <a href="#contact" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
                    <div class="position-relative">
                        <img src="assets/images/about-us.jpg" class="img-fluid rounded-4 shadow-lg" alt="Tentang Kami">
                        <div class="position-absolute bottom-0 end-0 bg-white p-4 rounded-4 shadow-lg m-4 d-none d-md-block">
                            <h2 class="fw-bold text-primary mb-0">5+</h2>
                            <p class="mb-0 text-muted small">Tahun Pengalaman</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5" data-aos="fade-left">
                    <h5 class="text-primary fw-bold text-uppercase mb-3">Tentang Kami</h5>
                    <h2 class="fw-bold mb-4" style="font-size: 2.5rem;">Kami Menyajikan Lebih Dari Sekadar Makanan</h2>
                    <p class="text-muted mb-4">
                        Waroeng Modern Bites didirikan dengan visi untuk menggabungkan cita rasa otentik dengan kenyamanan modern. Setiap hidangan dibuat dengan bahan-bahan pilihan terbaik dari petani lokal.
                    </p>
                    <ul class="list-unstyled mb-4">
                        <li class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                            <span>Bahan Baku Segar & Berkualitas</span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                            <span>Koki Berpengalaman & Profesional</span>
                        </li>
                        <li class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                            <span>Suasana Nyaman & Pelayanan Ramah</span>
                        </li>
                    </ul>
                    <a href="#menu" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Pelajari Selengkapnya</a>
                </div>
            </div>
        </div>
    </section>

    <section id="menu" class="py-5 bg-light position-relative">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-up">
                <h5 class="text-primary fw-bold text-uppercase">Menu Pilihan</h5>
                <h2 class="fw-bold">Terfavorit Minggu Ini</h2>
                <div class="mx-auto mt-3" style="width: 60px; height: 4px; background: var(--primary-grad); border-radius: 2px;"></div>
            </div>

            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-menu-modern">
                        <div class="card-img-wrapper">
                            <img src="assets/images/menu1.jpg" alt="Menu 1">
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold mb-0">Steak Premium</h5>
                                <span class="badge bg-warning text-dark">Rp 120k</span>
                            </div>
                            <p class="text-muted small">Daging sapi pilihan dengan saus spesial racikan koki kami.</p>
                            <a href="penjualan/index.php" class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-2">Pesan</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card-menu-modern">
                        <div class="card-img-wrapper">
                            <img src="assets/images/menu2.jpg" alt="Menu 2">
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold mb-0">Pasta Carbonara</h5>
                                <span class="badge bg-warning text-dark">Rp 85k</span>
                            </div>
                            <p class="text-muted small">Pasta creamy dengan topping jamur dan daging asap.</p>
                            <a href="penjualan/index.php" class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-2">Pesan</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card-menu-modern">
                        <div class="card-img-wrapper">
                            <img src="assets/images/menu3.jpg" alt="Menu 3">
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold mb-0">Burger Deluxe</h5>
                                <span class="badge bg-warning text-dark">Rp 95k</span>
                            </div>
                            <p class="text-muted small">Burger jumbo dengan keju meleleh dan sayuran segar.</p>
                            <a href="penjualan/index.php" class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-2">Pesan</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="penjualan/index.php" class="btn btn-modern">Lihat Semua Menu <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5 bg-white">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <h5 class="text-primary fw-bold text-uppercase">Hubungi Kami</h5>
                    <h2 class="fw-bold mb-4">Kami Siap Melayani Anda</h2>
                    <p class="text-muted mb-4">Punya pertanyaan atau ingin reservasi tempat? Jangan ragu untuk menghubungi kami melalui kontak di bawah ini.</p>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-3 rounded-circle text-primary me-3 shadow-sm">
                            <i class="fas fa-map-marker-alt fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Lokasi Kami</h6>
                            <p class="text-muted mb-0">Jl. Raya Kuliner No. 88, Jakarta Pusat</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-3 rounded-circle text-primary me-3 shadow-sm">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Email</h6>
                            <p class="text-muted mb-0">hello@modernbites.com</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-light p-3 rounded-circle text-primary me-3 shadow-sm">
                            <i class="fas fa-phone-alt fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Telepon / WhatsApp</h6>
                            <p class="text-muted mb-0">+62 812 3456 7890</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="card border-0 shadow-lg rounded-4 p-4">
                        <h4 class="fw-bold mb-4">Kirim Pesan</h4>
                        <form>
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-lg bg-light border-0" placeholder="Nama Anda">
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control form-control-lg bg-light border-0" placeholder="Email Anda">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control form-control-lg bg-light border-0" rows="4" placeholder="Pesan Anda..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-modern w-100">Kirim Pesan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container pb-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <img src="assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo" height="40" style="filter: brightness(0) invert(1);">
                        <span class="fw-bold fs-4 text-white">Modern Bites</span>
                    </div>
                    <p class="opacity-75">Kami berkomitmen menyajikan pengalaman kuliner terbaik dengan bahan berkualitas dan pelayanan sepenuh hati.</p>
                    <div class="d-flex gap-2 mt-4">
                        <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-btn"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-white fw-bold mb-3">Navigasi</h6>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><a href="#home" class="text-decoration-none text-reset">Beranda</a></li>
                        <li class="mb-2"><a href="#about" class="text-decoration-none text-reset">Tentang</a></li>
                        <li class="mb-2"><a href="#menu" class="text-decoration-none text-reset">Menu</a></li>
                        <li class="mb-2"><a href="#contact" class="text-decoration-none text-reset">Kontak</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-white fw-bold mb-3">Layanan</h6>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><a href="#" class="text-decoration-none text-reset">Dine In</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-reset">Take Away</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-reset">Delivery</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-reset">Catering</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-white fw-bold mb-3">Jam Operasional</h6>
                    <ul class="list-unstyled opacity-75">
                        <li class="d-flex justify-content-between mb-2"><span>Senin - Jumat</span> <span>10:00 - 22:00</span></li>
                        <li class="d-flex justify-content-between mb-2"><span>Sabtu - Minggu</span> <span>09:00 - 23:00</span></li>
                    </ul>
                </div>
            </div>
            <hr class="my-5 opacity-25">
            <div class="text-center opacity-50 small">
                &copy; <?= date('Y') ?> Waroeng Modern Bites. All Rights Reserved. Designed with <i class="fas fa-heart text-danger"></i> by Lopyta.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 50,
            duration: 800,
        });

        // Navbar Scroll Effect
        const navbar = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>