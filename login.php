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
</head>
<body>
    <div class="background-wrapper">
        <div class="wrapper">
            <div class="form-box login">
                <h2>Login</h2>
                <form id="ajax-login-form">
                    <div class="input-box">
                        <span class="icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label>Email</label>
                    </div>
                    <div class="input-box">
                        <input type="password" id="password" name="password" placeholder=" " required>
                        <label>Password</label>
                        <span class="icon-toggle" id="toggle-password"><i class="fas fa-eye-slash"></i></span>
                    </div>
                    <div class="remember-forgot">
                        <label><input type="checkbox"> Ingat saya</label>
                        <a href="#">Lupa Password?</a>
                    </div>
                    <button type="submit" class="btn" id="login-button">Masuk</button>
                    <div class="login-register">
                        <p>Belum punya akun? <a href="#" class="register-link">Daftar</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const loginForm = document.getElementById('ajax-login-form');
        const loginButton = document.getElementById('login-button');
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        // Toggle Password
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }

        // Handle Submit
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