document.addEventListener('DOMContentLoaded', () => {
    const signInForm = document.getElementById('sign-in-form');
    const signUpForm = document.getElementById('sign-up-form');
    
    // --- LOGIN AJAX ---
    signInForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const loginButton = document.getElementById('login-button');
        handleFormSubmit(this, 'auth/proses_login.php', loginButton, 'Login');
    });

    // --- REGISTER AJAX ---
    signUpForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const registerButton = document.getElementById('register-button');
        handleFormSubmit(this, 'auth/proses_register.php', registerButton, 'Sign up');
    });

    // --- FUNGSI UTAMA UNTUK MENGIRIM DATA ---
    function handleFormSubmit(form, url, button, defaultButtonText) {
        const formData = new FormData(form);
        
        // Tampilkan loading
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        fetch(url, {
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
                    timer: 2000,
                    showConfirmButton: false
                });

                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
        })
        .finally(() => {
            // Kembalikan tombol ke normal
            button.innerHTML = defaultButtonText;
            button.disabled = false;
        });
    }

    // --- Logika untuk toggle password ---
    const inputPassword = document.getElementById("input_password");
    const showPassword = document.getElementById("show_password");
    const inputPassword2 = document.getElementById("input_password2");
    const showPassword2 = document.getElementById("show_password2");

    showPassword.addEventListener("input", (e) => {
        inputPassword.type = e.target.checked ? "text" : "password";
    });

    showPassword2.addEventListener("input", (e) => {
        inputPassword2.type = e.target.checked ? "text" : "password";
    });
});