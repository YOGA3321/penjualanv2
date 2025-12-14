-- Database: `penjualan2`
-- Cleaned Master Database for Installation

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE IF NOT EXISTS `barang_masuk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pemasok_id` int NOT NULL,
  `user_id` int NOT NULL,
  `tanggal_masuk` datetime DEFAULT CURRENT_TIMESTAMP,
  `bukti_nota` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pemasok_id` (`pemasok_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk_detail`
--

CREATE TABLE IF NOT EXISTS `barang_masuk_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `barang_masuk_id` int NOT NULL,
  `item_id` int NOT NULL,
  `qty` int NOT NULL,
  `harga_beli` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `barang_masuk_id` (`barang_masuk_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE IF NOT EXISTS `cabang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_pusat` tinyint(1) DEFAULT '0',
  `jam_buka` time DEFAULT '10:00:00',
  `jam_tutup` time DEFAULT '22:00:00',
  `is_open` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cabang`
-- (Optional: Default Pusat)
INSERT INTO `cabang` (`id`, `nama_cabang`, `alamat`, `created_at`, `is_pusat`, `jam_buka`, `jam_tutup`, `is_open`) VALUES
(1, 'Pusat', 'Lokasi Pusat', NOW(), 1, '08:00:00', '22:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gudang_items`
--

CREATE TABLE IF NOT EXISTS `gudang_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `satuan` varchar(20) NOT NULL COMMENT 'e.g., kg, liter, pcs',
  `stok` int DEFAULT '0',
  `jenis` enum('bahan_baku','produk_jadi') NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_menu`
--

CREATE TABLE IF NOT EXISTS `kategori_menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cabang_id` int DEFAULT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_kat_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE IF NOT EXISTS `meja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cabang_id` int NOT NULL,
  `nomor_meja` varchar(10) NOT NULL,
  `qr_token` varchar(64) NOT NULL,
  `status` enum('kosong','terisi') DEFAULT 'kosong',
  PRIMARY KEY (`id`),
  KEY `cabang_id` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE IF NOT EXISTS `menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kategori_id` int NOT NULL,
  `cabang_id` int DEFAULT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `deskripsi` text,
  `harga` decimal(10,2) NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `gambar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `harga_promo` decimal(10,2) DEFAULT '0.00',
  `is_promo` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`),
  KEY `fk_menu_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mutasi_gudang`
--

CREATE TABLE IF NOT EXISTS `mutasi_gudang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `jenis_mutasi` enum('masuk_supplier','keluar_produksi','masuk_produksi','keluar_cabang','buang/rusak') NOT NULL,
  `qty` int NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pemasok`
--

CREATE TABLE IF NOT EXISTS `pemasok` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_pemasok` varchar(100) NOT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `alamat` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_detail`
--

CREATE TABLE IF NOT EXISTS `request_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `item_id` int NOT NULL,
  `qty_minta` int NOT NULL,
  `qty_kirim` int DEFAULT '0',
  `qty_terima` int DEFAULT '0',
  `status_item` enum('sesuai','kurang','lebih') DEFAULT 'sesuai',
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_stok`
--

CREATE TABLE IF NOT EXISTS `request_stok` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_request` varchar(50) NOT NULL,
  `cabang_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('pending','diproses','dikirim','selesai','batal') DEFAULT 'pending',
  `catatan_cabang` text,
  `catatan_gudang` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_kirim` datetime DEFAULT NULL,
  `tanggal_terima` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cabang_id` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE IF NOT EXISTS `reservasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  `meja_id` int NOT NULL,
  `waktu_reservasi` datetime NOT NULL,
  `durasi_menit` int DEFAULT '45',
  `status` enum('pending','checkin','selesai','batal') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `waktu_reservasi` (`waktu_reservasi`),
  KEY `meja_id` (`meja_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `user_id` int DEFAULT NULL,
  `meja_id` int NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `kode_voucher` varchar(50) DEFAULT NULL,
  `diskon` decimal(15,2) DEFAULT '0.00',
  `poin_didapat` int DEFAULT '0',
  `status_pembayaran` enum('pending','settlement','expire','cancel') DEFAULT 'pending',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `snap_token` varchar(255) DEFAULT NULL,
  `midtrans_id` varchar(100) DEFAULT NULL,
  `status_pesanan` enum('menunggu_bayar','menunggu_konfirmasi','diproses','siap_saji','selesai') DEFAULT 'menunggu_bayar',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uang_bayar` decimal(15,2) DEFAULT '0.00',
  `kembalian` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `meja_id` (`meja_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE IF NOT EXISTS `transaksi_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaksi_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `qty` int NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksi_id` (`transaksi_id`),
  KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `google_id` varchar(255) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cabang_id` int DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `level` enum('admin','karyawan','pelanggan','gudang') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL,
  `last_module` varchar(50) DEFAULT NULL,
  `poin` int DEFAULT '0',
  `foto` text,
  `alamat` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_cabang` (`cabang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
-- (Only Default Admin)
INSERT INTO `users` (`id`, `google_id`, `nama`, `email`, `password`, `cabang_id`, `no_hp`, `level`, `created_at`, `last_active`, `last_module`, `poin`, `foto`, `alamat`) VALUES
(1, '103201662146363136452', 'Admin', 'ageng.prayoga321@gmail.com', '$2y$10$NgJ8piqxSuqJ5LuqqKsWUeAvNS74iX6j/tsI1IfR5iFw65CXRiUUu', NULL, '090909', 'admin', NOW(), NULL, 'unknown', 0, 'https://lh3.googleusercontent.com/a/ACg8ocK5DWAgbhYERk_y4t1El2wRicJzO2sfSnWvOgvkEG2E79LimMMe=s96-c', '');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `tipe` enum('fixed','percent') DEFAULT 'fixed',
  `nilai` decimal(15,2) NOT NULL,
  `min_belanja` decimal(15,2) DEFAULT '0.00',
  `stok` int DEFAULT '100',
  `berlaku_sampai` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`pemasok_id`) REFERENCES `pemasok` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `barang_masuk_detail`
--
ALTER TABLE `barang_masuk_detail`
  ADD CONSTRAINT `barang_masuk_detail_ibfk_1` FOREIGN KEY (`barang_masuk_id`) REFERENCES `barang_masuk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `barang_masuk_detail_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `gudang_items` (`id`);

--
-- Constraints for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  ADD CONSTRAINT `fk_kat_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meja`
--
ALTER TABLE `meja`
  ADD CONSTRAINT `meja_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_menu` (`id`);

--
-- Constraints for table `request_detail`
--
ALTER TABLE `request_detail`
  ADD CONSTRAINT `request_detail_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `request_stok` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_detail_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `gudang_items` (`id`);

--
-- Constraints for table `request_stok`
--
ALTER TABLE `request_stok`
  ADD CONSTRAINT `request_stok_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`id`);

--
-- Constraints for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `transaksi_detail_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_detail_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
