-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Current
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `penjualanv2_production`
--

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `is_pusat` tinyint(1) DEFAULT 0,
  `jam_buka` time DEFAULT '10:00:00',
  `jam_tutup` time DEFAULT '22:00:00',
  `is_open` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabang`
-- (Hanya Cabang Pusat Default untuk mencegah error relasi)
--

INSERT INTO `cabang` (`id`, `nama_cabang`, `alamat`, `is_pusat`, `jam_buka`, `jam_tutup`, `is_open`) VALUES
(1, 'Pusat (Global)', 'Headquarters', 1, '09:00:00', '22:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kategori_menu`
--

CREATE TABLE `kategori_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_kat_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_meja` varchar(10) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `status` enum('kosong','terisi','reservasi') DEFAULT 'kosong',
  `qr_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `fk_meja_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_menu` varchar(100) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `harga_promo` decimal(15,2) DEFAULT 0.00,
  `is_promo` tinyint(1) DEFAULT 0,
  `stok` int(11) DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `cabang_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_menu_kategori` (`kategori_id`),
  KEY `fk_menu_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('admin','karyawan','pelanggan') DEFAULT 'pelanggan',
  `cabang_id` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `poin` int(11) DEFAULT 0,
  `last_active` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
-- (User Admin Utama)
-- Password default: 12345 (Silakan ubah setelah login)
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `level`, `cabang_id`, `poin`) VALUES
(1, 'Ageng Prayoga', 'ageng.prayoga321@gmail.com', '$2y$10$e/h.t/h.t/h.t/h.t/h.tu.u.u.u.u.u.u.u.u.u.u.u.u.u.u', 'admin', NULL, 0);
-- Note: Password hash di atas adalah contoh dummy untuk '12345'. 
-- Jika Anda punya hash asli, silakan replace string '$2y$10$...' di atas.

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `tipe` enum('fixed','percent') DEFAULT 'fixed',
  `nilai` decimal(15,2) NOT NULL,
  `min_belanja` decimal(15,2) DEFAULT 0.00,
  `stok` int(11) DEFAULT 100,
  `berlaku_sampai` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE `reservasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `meja_id` int(11) NOT NULL,
  `waktu_reservasi` datetime NOT NULL,
  `durasi_menit` int(11) DEFAULT 60,
  `status` enum('pending','checkin','selesai','batal') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_res_user` (`user_id`),
  KEY `fk_res_meja` (`meja_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) NOT NULL,
  `meja_id` int(11) DEFAULT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `uang_bayar` decimal(15,2) DEFAULT 0.00,
  `kembalian` decimal(15,2) DEFAULT 0.00,
  `status_pembayaran` enum('pending','settlement','cancel','expire','failure') DEFAULT 'pending',
  `metode_pembayaran` varchar(20) DEFAULT 'tunai',
  `status_pesanan` enum('menunggu_konfirmasi','menunggu_bayar','diproses','siap_saji','selesai','cancel') DEFAULT 'menunggu_bayar',
  `snap_token` varchar(255) DEFAULT NULL,
  `midtrans_id` varchar(100) DEFAULT NULL,
  `kode_voucher` varchar(50) DEFAULT NULL,
  `diskon` decimal(15,2) DEFAULT 0.00,
  `poin_didapat` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `fk_trx_meja` (`meja_id`),
  KEY `fk_trx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaksi_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detail_trx` (`transaksi_id`),
  KEY `fk_detail_menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  ADD CONSTRAINT `fk_kat_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meja`
--
ALTER TABLE `meja`
  ADD CONSTRAINT `fk_meja_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_menu_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_menu` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD CONSTRAINT `fk_res_meja` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_trx_meja` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `fk_detail_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  ADD CONSTRAINT `fk_detail_trx` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;