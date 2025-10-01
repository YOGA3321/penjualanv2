const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');

// Logika untuk slide antara form login dan register
registerLink.addEventListener('click', (e) => {
    e.preventDefault(); // Mencegah link berpindah halaman
    wrapper.classList.add('active');
});

loginLink.addEventListener('click', (e) => {
    e.preventDefault(); // Mencegah link berpindah halaman
    wrapper.classList.remove('active');
});


// == JAVASCRIPT BARU UNTUK TOGGLE PASSWORD ==

// Fungsi generik untuk menangani toggle password
const setupPasswordToggle = (toggleId, passwordId) => {
    const togglePassword = document.getElementById(toggleId);
    const password = document.getElementById(passwordId);

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function () {
            // Dapatkan ikon di dalam span
            const icon = this.querySelector('i');
            // Toggle tipe input
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle kelas ikon
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
};

// Panggil fungsi untuk setiap form
setupPasswordToggle('toggle-login-password', 'login-password');
setupPasswordToggle('toggle-register-password', 'register-password');
