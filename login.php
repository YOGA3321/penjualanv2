<?php
session_start();
// Jika sudah login, redirect sesuai level
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['level'] == 'admin' || $_SESSION['level'] == 'karyawan') {
        header("Location: admin/");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="shortcut icon" href="assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">
    <style>
        /* --- PERBAIKAN CSS UTAMA --- */
        
        /* 1. Override wrapper agar tinggi fleksibel (tidak terpotong) */
        .wrapper {
            height: auto !important; /* PENTING: Agar kotak bisa memanjang */
            min-height: 440px;       /* Tinggi minimal agar tetap cantik saat awal */
            padding-bottom: 30px;    /* Memberi ruang napas di bawah tombol */
        }

        /* Styling Header */
        .login-header { margin-bottom: 30px; text-align: center; }
        .login-header h2 { font-size: 2.5em; margin-bottom: 10px; color: #fff; }
        .login-header p { color: rgba(255,255,255,0.8); font-size: 0.9em; }
        
        /* Styling Divider */
        .divider {
            display: flex; align-items: center; text-align: center; 
            color: rgba(255,255,255,0.6); margin: 20px 0;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; border-bottom: 1px solid rgba(255,255,255,0.3);
        }
        .divider:not(:empty)::before { margin-right: .5em; }
        .divider:not(:empty)::after { margin-left: .5em; }

        /* Styling Tombol Google */
        .btn-google {
            background-color: #fff; color: #333; 
            display: flex; align-items: center; justify-content: center; 
            gap: 10px; padding: 12px; border-radius: 50px; 
            text-decoration: none; font-weight: 600; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        .btn-google:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
        .btn-google img { width: 24px; height: 24px; }

        /* Animasi Form Manual */
        #ajax-login-form { 
            display: none; 
            animation: fadeIn 0.5s; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(-10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>
</head>
<body>
    <div class="background-wrapper">
        <div class="wrapper">
            <div class="form-box login">
                <div class="login-header">
                    <h2>Login</h2>
                    <p>Selamat Datang Kembali!</p>
                </div>

                <?php 
                if(file_exists('auth/google_config.php')) {
                    require_once 'auth/google_config.php';
                    $login_url = $client->createAuthUrl();
                    ?>
                    <a href="<?= $login_url ?>" class="btn-google">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google Logo">
                        Masuk dengan Google
                    </a>
                    <?php
                }
                ?>

                <div class="divider">atau</div>

                <div class="text-center" id="manual-login-link">
                    <a onclick="document.getElementById('ajax-login-form').style.display='block'; document.getElementById('manual-login-link').style.display='none';" 
                       style="color:#fff; cursor:pointer; font-weight:500; text-decoration:none; display:inline-block; padding:5px; border-bottom:1px dashed rgba(255,255,255,0.5);">
                       <i class="fas fa-key me-2"></i> Login dengan Password
                    </a>
                </div>

                <form id="ajax-login-form" style="display: none; margin-top: 20px;">
                    <div class="input-box">
                        <span class="icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label>Email</label>
                    </div>
                    <div class="input-box">
                        <input type="password" id="password" name="password" placeholder=" " required>
                        <label>Password</label>
                        <span class="icon-toggle" id="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #fff;">
                            <i class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                    
                    <div class="remember-forgot">
                        <label><input type="checkbox"> Ingat saya</label>
                        </div>
                    
                    <button type="submit" class="btn" id="login-button">Masuk</button>
                </form>

                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle Password Visibility
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                if (type === 'text') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }

        // Handle Login Submit
        const loginForm = document.getElementById('ajax-login-form');
        const loginButton = document.getElementById('login-button');

        if (loginForm) {
            loginForm.addEventListener('submit', function(event) {
                event.preventDefault();
                loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                loginButton.disabled = true;

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
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                        loginButton.innerHTML = 'Masuk';
                        loginButton.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.' });
                    loginButton.innerHTML = 'Masuk';
                    loginButton.disabled = false;
                });
            });
        }
    });
    </script>
</body>
</html>