document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('ajax-login-form');
    const loginButton = document.getElementById('login-button');

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah halaman refresh

            // Tampilkan loading di tombol
            loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            loginButton.disabled = true;

            // FormData akan otomatis mengambil semua input yang memiliki atribut 'name'
            const formData = new FormData(this);

            // === PERBAIKAN PATH FETCH ===
            fetch('auth/proses_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Arahkan ke halaman dari respons PHP setelah notifikasi
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan. Periksa koneksi atau hubungi administrator.'
                });
            })
            .finally(() => {
                // Kembalikan tombol ke keadaan semula
                loginButton.innerHTML = 'Masuk';
                loginButton.disabled = false;
            });
        });
    }

    // Logika untuk toggle password
    const togglePassword = document.getElementById('toggle-password');
    const password = document.getElementById('password');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // Ganti ikon mata
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
});