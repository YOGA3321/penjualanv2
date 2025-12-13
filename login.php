<?php
session_start();
// Jika sudah login, redirect sesuai level
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['level'] == 'admin' || $_SESSION['level'] == 'karyawan') {
        header("Location: admin/");
    } elseif ($_SESSION['level'] == 'gudang') {
        header("Location: gudang/");
    } else {
        header("Location: penjualan/");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Waroeng Modern Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-color: #ffffff;
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #0f2027;
            background: -webkit-linear-gradient(to right, #2c5364, #203a43, #0f2027);
            background: linear-gradient(to right, #2c5364, #203a43, #0f2027);
            overflow: hidden;
            position: relative;
        }

        /* Ambient Background Elements */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
        }
        
        body::before {
            width: 400px;
            height: 400px;
            background: #764ba2;
            top: -100px;
            left: -100px;
            animation: float 10s infinite alternate;
        }

        body::after {
            width: 300px;
            height: 300px;
            background: #667eea;
            bottom: -50px;
            right: -50px;
            animation: float 8s infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }

        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .logo-area {
            margin-bottom: 25px;
        }
        
        .logo-area img {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
        }

        h2 {
            color: var(--text-color);
            font-weight: 600;
            font-size: 28px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        p.welcome-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 35px;
        }

        /* View Containers */
        .view-section {
            transition: all 0.4s ease;
        }

        /* Initial View Styles */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: #ffffff;
            color: #333333;
            width: 100%;
            padding: 14px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            margin-bottom: 20px;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-google:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            background: #f8f9fa;
        }

        .btn-google img {
            width: 20px;
            height: 20px;
        }

        .manual-login-trigger {
            margin-top: 15px;
        }

        .manual-login-trigger a {
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.3);
            padding-bottom: 2px;
        }

        .manual-login-trigger a:hover {
            color: #ffffff;
            border-bottom-color: #ffffff;
        }

        /* Password Form Styles */
        .input-group {
            margin-bottom: 20px;
            position: relative;
            text-align: left;
        }

        .input-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 16px;
        }

        .input-wrapper input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            padding: 14px 14px 14px 45px;
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #fff;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            cursor: pointer;
            transition: color 0.3s;
            background: none;
            border: none;
        }

        .back-btn:hover {
            color: rgba(255, 255, 255, 0.9);
        }

        /* Utilities */
        .hidden {
            display: none;
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

    </style>
</head>
<body>

    <div class="container">
        <div class="glass-card">
            <div class="logo-area">
                <!-- Gunakan icon font awesome jika gambar tidak ada, atau uncomment gambar jika ingin pakai logo -->
                <i class="fas fa-utensils fa-2x" style="color: #fff; opacity: 0.8; margin-bottom: 10px;"></i>
                <!-- <img src="assets/images/logo.png" alt="Logo"> -->
            </div>
            
            <div id="initial-view" class="view-section fade-in">
                <h2>Selamat Datang</h2>
                <p class="welcome-text">Silahkan masuk untuk melanjutkan</p>

                <?php 
                // Cek apakah config google di set
                if(file_exists('auth/google_config.php')) {
                    require_once 'auth/google_config.php';
                    $login_url = $client->createAuthUrl();
                } else {
                    $login_url = "#";
                }
                ?>
                
                <a href="<?= $login_url ?>" class="btn-google">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="G">
                    Masuk dengan Google
                </a>

                <div class="manual-login-trigger">
                    <a onclick="showPasswordForm()">Opsi Lainnya</a>
                </div>
            </div>

            <div id="password-view" class="view-section hidden">
                <h2>Login Akun</h2>
                <p class="welcome-text">Masukkan email dan password Anda</p>

                <form id="ajax-login-form">
                    <div class="input-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password-input" placeholder="********" required>
                            <i class="fas fa-eye toggle-password" id="toggle-password"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="login-button">
                        Masuk Sekarang
                    </button>
                    
                    <button type="button" class="back-btn" onclick="showInitialView()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                </form>
            </div>

        </div>
    </div>

    <script>
        function showPasswordForm() {
            document.getElementById('initial-view').classList.add('hidden');
            document.getElementById('initial-view').classList.remove('fade-in');
            
            const passView = document.getElementById('password-view');
            passView.classList.remove('hidden');
            passView.classList.add('fade-in');
        }

        function showInitialView() {
            document.getElementById('password-view').classList.add('hidden');
            document.getElementById('password-view').classList.remove('fade-in');
            
            const initView = document.getElementById('initial-view');
            initView.classList.remove('hidden');
            initView.classList.add('fade-in');
        }

        // Toggle Password Visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
            const input = document.getElementById('password-input');
            const icon = this;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // AJAX Login Handling
        document.getElementById('ajax-login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('login-button');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            const formData = new FormData(this);

            fetch('auth/proses_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Masuk!',
                        text: 'Mengalihkan...',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#fff',
                        iconColor: '#764ba2'
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message,
                        background: '#fff'
                    });
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan sistem',
                    background: '#fff'
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    </script>
    
    <?php if(isset($_SESSION['swal'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['swal']['icon'] ?>',
            title: '<?= $_SESSION['swal']['title'] ?>',
            text: '<?= $_SESSION['swal']['text'] ?>',
            background: '#fff'
        });
    </script>
    <?php unset($_SESSION['swal']); endif; ?>

</body>
</html>