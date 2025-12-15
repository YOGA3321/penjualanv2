# Aplikasi Penjualan (Waroeng Modern Bites)

Aplikasi Point of Sales (POS) berbasis Web dengan fitur manajemen stok, pesanan, dan integrasi pembayaran Midtrans.

## Fitur Utama
*   **Multi Role**: Admin, Karyawan, Gudang, Pelanggan.
*   **Auto-Admin**: User yang mendaftar pertama kali otomatis menjadi Admin.
*   **Installer**: Wizard instalasi otomatis untuk setup database.
*   **Integrasi Midtrans**: Pembayaran digital (QRIS, VA, E-Wallet).
*   **Login Google**: Masuk dengan akun Google (OAuth).
*   **Manajemen Stok**: Opname, Mutasi, dan Request Stok antar cabang.

## Persyaratan Sistem
*   PHP >= 8.0
*   MySQL >= 5.7
*   Composer
*   Internet (Untuk Google Login & Midtrans)

## Cara Install

### 1. Clone Repository (atau Copy File)
Pastikan file berada di folder web server (cth: `c:\laragon\www\penjualanv2`).

### 2. Install Dependencies
Jalankan perintah berikut di terminal:
```bash
composer install
```
Aplikasi menggunakan `vlucas/phpdotenv` untuk keamanan kredensial.

### 3. Setup Konfigurasi (.env)
*   **Otomatis**: Jika Anda menggunakan **Installer** (langkah 4), file `.env` akan dibuat secara **otomatis**.
*   **Manual**: Jika ingin setup manual, copy file `.env.example` menjadi `.env` dan isi kredensial DB Anda.
    *   `GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET`: Dari Google Cloud Console.
    *   `MIDTRANS_SERVER_KEY` & `CLIENT_KEY`: Dari Dashboard Midtrans.

### 4. Instalasi Database & Aplikasi
Buka browser dan akses aplikasi:
`http://localhost/penjualanv2/`

Anda akan diarahkan ke halaman **Instalasi Sistem**.
1.  Pilih Mode **Otomatis** jika database belum ada.
2.  Masukkan Host, User, Pass, dan Nama DB.
3.  Klik **Install Aplikasi**.
    *   Installer akan membuat database (jika mode otomatis).
    *   Membuat file `.env` berisi konfigurasi database Anda.
    *   Membuat user admin default.

### 5. Login Pertama (Auto-Admin)
1.  Klik **Masuk dengan Google** di halaman login.
2.  User pertama yang login akan otomatis mendapatkan level **ADMIN**.
3.  User selanjutnya akan menjadi **PELANGGAN** biasa.

## Struktur Folder
*   `/admin`: Dashboard Admin & Kasir.
*   `/auth`: Logika Login & Koneksi.
*   `/gudang`: Modul Manajemen Stok.
*   `/install`: Wizard Instalasi.
*   `/penjualan`: Halaman Frontend Pelanggan.
*   `/vendor`: Library PHP (Composer).

## Catatan Keamanan
*   File `.env` berisi kunci rahasia. **JANGAN DI-UPLOAD** ke repository publik (sudah di-ignore oleh git).
*   Pastikan `google_config.php` dan file transaksi Midtrans menggunakan `$_ENV` (sudah dikonfigurasi).

## Troubleshoot
*   **Google Login Error mismatch**: Pastikan URL di Google Console cocok dengan `BASE_URL` aplikasi + `/auth/google_callback.php`.
*   **Midtrans Error**: Cek `MIDTRANS_SERVER_KEY` di `.env` dan pastikan `MIDTRANS_IS_PRODUCTION` sesuai mode (false untuk sandbox).
