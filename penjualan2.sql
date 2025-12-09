-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 05:52 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u116133173_penjualan2`
--

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `id` int(11) NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_pusat` tinyint(1) DEFAULT 0,
  `jam_buka` time DEFAULT '10:00:00',
  `jam_tutup` time DEFAULT '22:00:00',
  `is_open` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cabang`
--

INSERT INTO `cabang` (`id`, `nama_cabang`, `alamat`, `created_at`, `is_pusat`, `jam_buka`, `jam_tutup`, `is_open`) VALUES
(1, 'Surabaya', 'sby kota', '2025-12-07 07:24:19', 1, '10:00:00', '22:00:00', 1),
(2, 'Gresik', 'Jalan gresik', '2025-12-07 09:19:39', 0, '10:00:00', '22:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kategori_menu`
--

CREATE TABLE `kategori_menu` (
  `id` int(11) NOT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori_menu`
--

INSERT INTO `kategori_menu` (`id`, `cabang_id`, `nama_kategori`) VALUES
(1, 1, 'Makanan Abot'),
(2, 1, 'Camilan');

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id` int(11) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `nomor_meja` varchar(10) NOT NULL,
  `qr_token` varchar(64) NOT NULL,
  `status` enum('kosong','terisi') DEFAULT 'kosong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`id`, `cabang_id`, `nomor_meja`, `qr_token`, `status`) VALUES
(1, 1, '1', '4709c91d9c6b294c2757550bb2765014', 'terisi'),
(2, 1, '2', 'e8a4ff4726dbcb6fc19061e15fe6c8f6', 'terisi'),
(3, 1, '3', 'cef9e2d2258dd72de9279cb42d9de6d1', 'terisi'),
(4, 1, '4', '3065517c5f3d37dc3be581d2f34a08b6', 'terisi'),
(5, 1, '5', 'ce41268fad4da127970d49aeb067af2f', 'kosong'),
(6, 1, '6', '5683fc8a44b269e37a543ff0aa6fd635', 'kosong'),
(7, 1, '7', 'd17e2caafd0b52a630c6bf39a32cc876', 'kosong'),
(8, 1, '8', '0407fc03cc3ce6554541eead155cc4f6', 'kosong'),
(9, 1, '9', '76e4f6d692f6a1ed763c1cd08bc991a0', 'kosong'),
(10, 1, '10', '3dd0b3f350bfbbc678e56a8d1aa0ee41', 'kosong'),
(11, 1, '11', '8d1073d37601015c215b12cc3e5b5cb7', 'kosong'),
(12, 1, '12', '245af0378f94904c097c8b68ef2624ba', 'kosong'),
(13, 1, '13', 'b30566483026c50400b8c170d71f7830', 'kosong'),
(14, 1, '14', '5e5999deffe0c6450b62cc9de482ade0', 'kosong'),
(15, 1, '15', '65aee1165d82a58db9d3f9434ef6dcbd', 'kosong'),
(16, 1, '16', '892a4a786af97579b2f3ea08a0c5726a', 'kosong'),
(17, 1, '17', 'bf93e67f70af93b5c1a4dbe2e427bc9f', 'kosong'),
(18, 1, '18', '36d599819fd11fc572b3a9a235deec1c', 'kosong'),
(19, 1, '19', 'c12e253e97a3b3b5d057afb7902c9473', 'kosong'),
(20, 1, '20', 'b198dcfefd859c447b5bd360e85ba3fc', 'kosong');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `harga_promo` decimal(10,2) DEFAULT 0.00,
  `is_promo` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `kategori_id`, `cabang_id`, `nama_menu`, `deskripsi`, `harga`, `stok`, `gambar`, `is_active`, `harga_promo`, `is_promo`) VALUES
(1, 1, 1, 'Pempek', '', 10000.00, 53, NULL, 1, 5000.00, 1),
(2, 1, NULL, 'Nasi Goreng', '', 100000.00, 95, 'assets/images/menu/693533c7e89a3.webp', 1, 70000.00, 1),
(3, 1, 1, 'Nasi uduk', '', 20000.00, 97, NULL, 1, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE `reservasi` (
  `id` int(11) NOT NULL,
  `uuid` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meja_id` int(11) NOT NULL,
  `waktu_reservasi` datetime NOT NULL,
  `durasi_menit` int(11) DEFAULT 45,
  `status` enum('pending','checkin','selesai','batal') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reservasi`
--

INSERT INTO `reservasi` (`id`, `uuid`, `user_id`, `meja_id`, `waktu_reservasi`, `durasi_menit`, `status`, `created_at`) VALUES
(1, 'RES-69353278baac4', 4, 3, '2025-12-07 17:30:00', 45, 'selesai', '2025-12-07 07:53:28'),
(2, 'RES-69354638145ff', 6, 8, '2025-12-07 17:30:00', 45, 'pending', '2025-12-07 09:17:44'),
(3, 'RES-69354833eeb19', 7, 3, '2025-12-07 17:00:00', 45, 'selesai', '2025-12-07 09:26:11');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `meja_id` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `kode_voucher` varchar(50) DEFAULT NULL,
  `diskon` decimal(15,2) DEFAULT 0.00,
  `poin_didapat` int(11) DEFAULT 0,
  `status_pembayaran` enum('pending','settlement','expire','cancel') DEFAULT 'pending',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `snap_token` varchar(255) DEFAULT NULL,
  `midtrans_id` varchar(100) DEFAULT NULL,
  `status_pesanan` enum('menunggu_bayar','menunggu_konfirmasi','diproses','siap_saji','selesai') DEFAULT 'menunggu_bayar',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `uang_bayar` decimal(15,2) DEFAULT 0.00,
  `kembalian` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `uuid`, `user_id`, `meja_id`, `nama_pelanggan`, `total_harga`, `kode_voucher`, `diskon`, `poin_didapat`, `status_pembayaran`, `metode_pembayaran`, `snap_token`, `midtrans_id`, `status_pesanan`, `created_at`, `uang_bayar`, `kembalian`) VALUES
(1, 'TRX-69352c5ebb14a', NULL, 1, 'kombon', 70000.00, NULL, 0.00, 0, 'cancel', 'midtrans', '9e30628e-af29-4d44-bd16-91388acb217c', NULL, '', '2025-12-07 07:27:26', 0.00, -70000.00),
(2, 'b6d84a11-9cd8-4697-9ebb-95480d3a5173', NULL, 2, 'RUDI', 20000.00, NULL, 0.00, 0, 'settlement', 'tunai', NULL, NULL, 'selesai', '2025-12-07 07:31:58', 0.00, 0.00),
(3, 'TRX-69352e3dd65c6', NULL, 2, 'nanang', 110000.00, NULL, 0.00, 0, 'cancel', 'midtrans', 'a7837066-9a24-4124-8625-8341882a6b5d', NULL, '', '2025-12-07 07:35:25', 0.00, -110000.00),
(4, 'TRX-69352e9a8851c', NULL, 1, 'kombon', 290000.00, NULL, 0.00, 0, 'pending', 'midtrans', 'fabb0138-e334-4951-be34-5e33d95aa371', NULL, 'menunggu_bayar', '2025-12-07 07:36:58', 0.00, -290000.00),
(5, '78fb65f0-4862-4984-8ff0-278ae946429d', 4, 3, 'BAGUS', 13500.00, 'LONGOR', 1500.00, 1, 'settlement', 'tunai', NULL, NULL, 'selesai', '2025-12-07 07:54:22', 0.00, 0.00),
(6, '273ca16e-f7bb-47f4-9906-250ab1c15d29', 4, 3, 'KUIS', 45000.00, 'LONGOR', 5000.00, 4, 'settlement', 'tunai', '86a5d07b-0a61-4b75-921a-04e73990204c', 'PENJUALAN-251207483', 'diproses', '2025-12-07 07:55:27', 50000.00, 5000.00),
(7, 'TRX-69353f598ec54', NULL, 2, 'kombon', 75000.00, NULL, 0.00, 0, 'pending', 'midtrans', '096d8a88-db5e-4d9c-93d8-ae1e53e5eb36', NULL, 'menunggu_bayar', '2025-12-07 08:48:25', 0.00, -75000.00),
(8, 'TRX-6935471ca7b4f', NULL, 4, 'Budii', 85500.00, 'LONGOR', 9500.00, 0, 'pending', 'midtrans', 'a5a5a5c0-0642-44d3-862b-74596c3e4a11', NULL, 'menunggu_bayar', '2025-12-07 09:21:32', 0.00, -85500.00),
(9, '5d1679a9-6314-4433-b71c-3697b5d929ed', 7, 3, 'ayla lopy', 57500.00, 'KIWCOWO', 57500.00, 5, 'settlement', 'tunai', '29b710c8-c32c-4097-9af1-84c93b2f7bb7', 'PENJUALAN-251207894', 'selesai', '2025-12-07 09:27:33', 60000.00, 2500.00),
(10, '7cb3b40f-388e-4400-8b60-3f8433a17782', 7, 3, 'ayla lopy', 126000.00, 'LONGOR', 14000.00, 12, 'settlement', 'midtrans', '04ce6f3f-a2c8-42b3-b6c0-01062fc4cb7e', 'RESTO-251207773', 'diproses', '2025-12-07 11:05:35', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `catatan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi_detail`
--

INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `menu_id`, `qty`, `harga_satuan`, `subtotal`, `catatan`) VALUES
(1, 1, 1, 7, 10000.00, 70000.00, NULL),
(2, 2, 1, 2, 10000.00, 20000.00, NULL),
(3, 3, 1, 11, 10000.00, 110000.00, NULL),
(4, 4, 1, 29, 10000.00, 290000.00, NULL),
(5, 5, 1, 3, 5000.00, 15000.00, NULL),
(6, 6, 1, 10, 5000.00, 50000.00, NULL),
(7, 7, 2, 1, 70000.00, 70000.00, NULL),
(8, 7, 1, 1, 5000.00, 5000.00, NULL),
(9, 8, 2, 1, 70000.00, 70000.00, NULL),
(10, 8, 3, 1, 20000.00, 20000.00, NULL),
(11, 8, 1, 1, 5000.00, 5000.00, NULL),
(12, 9, 2, 1, 70000.00, 70000.00, NULL),
(13, 9, 1, 1, 5000.00, 5000.00, NULL),
(14, 9, 3, 2, 20000.00, 40000.00, NULL),
(15, 10, 2, 2, 70000.00, 140000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cabang_id` int(11) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `level` enum('admin','karyawan','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active` datetime DEFAULT NULL,
  `poin` int(11) DEFAULT 0,
  `foto` text DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `nama`, `email`, `password`, `cabang_id`, `no_hp`, `level`, `created_at`, `last_active`, `poin`, `foto`, `alamat`) VALUES
(1, '112452179974592495944', 'Farrel Prastita', 'prastitafarrell@gmail.com', '$2y$10$V4AGScSihk.UXC3if7iZ5OhbRe4.iBRfrhOc3zmxTLWDuPCwq0F6O', NULL, NULL, 'admin', '2025-12-07 07:19:33', '2025-12-07 16:33:30', 0, 'https://lh3.googleusercontent.com/a/ACg8ocKpPQGsyqaCc-Ks7r7TFu8hJfwoLNRJ2fqc4oybauZ6GppQMQ=s96-c', NULL),
(2, '103201662146363136452', 'YOGABD _46', 'ageng.prayoga321@gmail.com', '$2y$10$Xzayg/EF9WUzBWKzZ33yGup0OW20vcEtc//QfhhtLqlA3j3ziB5di', NULL, NULL, 'admin', '2025-12-07 07:20:08', '2025-12-07 21:56:05', 0, 'https://lh3.googleusercontent.com/a/ACg8ocK5DWAgbhYERk_y4t1El2wRicJzO2sfSnWvOgvkEG2E79LimMMe=s96-c', NULL),
(3, '111717584846397613234', 'Safa Maulana Efendi', 'ctzefendi@gmail.com', '$2y$10$bSUXbjZSySjvVPnFrWEswupH01sbUyYGMcjtRojMBhaDe0dRtT4LG', NULL, NULL, 'admin', '2025-12-07 07:44:51', '2025-12-07 15:27:21', 0, 'https://lh3.googleusercontent.com/a/ACg8ocJMZkNQRNzSVvdQCJ-fygzjaMzaznZHypwBsI8pz4BQcgf_jeA=s96-c', NULL),
(4, '102964880243869569094', 'Editting 333', 'editting463@gmail.com', '$2y$10$e4o2qYjIg3QCiDLqPTa1n.troKORe9RxjHpU4JkhBk7WsX6gKklUC', NULL, NULL, 'pelanggan', '2025-12-07 07:52:41', '2025-12-07 16:26:25', 5, 'https://lh3.googleusercontent.com/a/ACg8ocIA3BI772qZGzFeJUKDyA8_F-HLvv2wX_M8UJR0buKIFgeEuw=s96-c', NULL),
(5, '116090965361332364126', 'Safa Maulana Efendi', 'safam.efendi@gmail.com', '$2y$10$ZDcx.A8ap7ZPzql0tdMu..wASdX7ay7GYtmagUmmYhJkFBh6u/nYy', NULL, NULL, 'pelanggan', '2025-12-07 09:11:54', '2025-12-07 16:13:53', 0, 'https://lh3.googleusercontent.com/a/ACg8ocJ4NrLqoQpnQqVvJEG8uBXCzMxM6HcOwGaoYcFPheVO64U1xg=s96-c', NULL),
(6, '106943346532586441531', 'PendiX', 'safa.efendi@gmail.com', '$2y$10$yLSka5/Mf8NzGIUpY42fm.EUtTmL9Rbe7cWvyX4P/lBvoDVaY50AO', NULL, NULL, 'pelanggan', '2025-12-07 09:15:38', NULL, 0, 'https://lh3.googleusercontent.com/a/ACg8ocKzmPMy3iMpf1vryqM-5ipNyqyzx4Ois2l6tb0j6o7JkgZM9ING=s96-c', NULL),
(7, '107164099205175610272', 'ayla lopy', 'aylalopyta21@gmail.com', '$2y$10$mCXr1ZkMMcXM9BZBnKD38.4ZznHwARG5JgWYlaaXv3RQ/s99Vas72', NULL, NULL, 'pelanggan', '2025-12-07 09:17:40', '2025-12-07 18:04:37', 17, 'https://lh3.googleusercontent.com/a-/ALV-UjW_2N6GWiRIWAqa6W2X9DXBR82ilxDUG1dlaiCoWPk5vTIZsMg_DpoGN1_f9V_WTsfkmU3bZ7B01zIiTPNYvkihmmD7zs6JOSPD70qHge-vRdUxM9W0QxpkjIXQyzXu8L5WksJ6-sjhXPAN8frB_YDOOq3GYhIDXmMnJZ2zS4fAa7PKOVWg69fEguubJeq6jIosVeZ8e5lap2RBneeuAgf5HcL7ETuGV9h32ryJ51F8ZCLTHb8676W2XyGAA9uNuHv3xYGT1ntqtz8W_nHEkA1VwxfsTdC3PesEWVyTyMgZmguYHSNofl-Zo9K_fkfH_SreSDffQQ9Lfp1qi5K_xebS4zAvovwVzlXQK-GDUxwBbv58sAN4GAldIbp2E-nbo8k2BRZ9hPdC3H7ikOLwP5b0bhXAwchvbsgqqtZMvitCFU5b9dzUVgLQlDa-G-5T9plzS65xoRJRWHd1-7ILVJS1SJJvGgv_vOKhlQX9nv_ERMX0R--93vjsocKTd48Clpr6ch2Bs4xEPmcsKz1igzcwLneFsk7H9sW-WOGHF49-WmqURyDGW3aHbll2n9f15dTVVHvZwmoPVFt3v9X6wI0vnZweODQxoAuT56tfqwy1QaO9-hQGaO6kybGdohPLqiasRHtWCjc9mOMk57nbmvLtEld_bSYJ1VQwvkWTLo3s9Cj1DF0ry4RWTHJbQCEZ4a0tiaSt6wnPNtWiPL8HmQNXEjkDTvLW-aLvj5UTcbhC7EashhbWfZBhvqD-HD3UxTFkQMtfiQpwsYsYAXYdOGna3IglLVWo5XidfDomOg5ezz3L6-eMyRzpOYP59sHmtVsHD7V-kHobEQe2e1WOA334O4eU91jslejQ4swF0G8JWw-fTJc0qtQDZtaXfbfd9Gh3sclCjgWWAnSMHeN6jKnzAJ26yNtGQCI_NLklq5tNcqFhhf8Wg8BStgxeYvKmT5fi1unq1p30TgFnxIj3oDT6hQRMV7AMpJViBJ7Yhej6FMTXNSBlOojNrIPvJyw-dgaslZu1i1HTpsBRMZWNiovXZENfLHLIBL6M3eDv9gVe-tDugDiiwnA=s96-c', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `kode` varchar(50) NOT NULL,
  `tipe` enum('fixed','percent') DEFAULT 'fixed',
  `nilai` decimal(15,2) NOT NULL,
  `min_belanja` decimal(15,2) DEFAULT 0.00,
  `stok` int(11) DEFAULT 100,
  `berlaku_sampai` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `kode`, `tipe`, `nilai`, `min_belanja`, `stok`, `berlaku_sampai`, `created_at`) VALUES
(1, 'LONGOR', 'percent', 10.00, 5000.00, 96, '2025-12-08', '2025-12-07 07:41:00'),
(2, 'KIWCOWO', 'percent', 50.00, 100000.00, 99, '2025-12-12', '2025-12-07 08:00:15'),
(3, 'IZO0GQNQ', 'percent', 20.00, 100000.00, 100, '2025-12-08', '2025-12-07 09:18:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cabang`
--
ALTER TABLE `cabang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kat_cabang` (`cabang_id`);

--
-- Indexes for table `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `fk_menu_cabang` (`cabang_id`);

--
-- Indexes for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `waktu_reservasi` (`waktu_reservasi`),
  ADD KEY `meja_id` (`meja_id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `meja_id` (`meja_id`);

--
-- Indexes for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_cabang` (`cabang_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cabang`
--
ALTER TABLE `cabang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservasi`
--
ALTER TABLE `reservasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `meja_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_cabang` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_menu` (`id`);

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
