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
    <script src="assets/js/login.js"></script>
</body>
</html>