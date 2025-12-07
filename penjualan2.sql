-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 07, 2025 at 06:41 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `penjualan2`
--

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `id` int NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_pusat` tinyint(1) DEFAULT '0',
  `jam_buka` time DEFAULT '10:00:00',
  `jam_tutup` time DEFAULT '22:00:00',
  `is_open` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cabang`
--

INSERT INTO `cabang` (`id`, `nama_cabang`, `alamat`, `created_at`, `is_pusat`, `jam_buka`, `jam_tutup`, `is_open`) VALUES
(1, 'Surabaya', 'surabaya selatan', '2025-11-26 15:47:09', 1, '08:00:00', '22:00:00', 1),
(2, 'Jogja', 'Jln. Jogjakarta', '2025-12-02 15:15:08', 0, '10:00:00', '22:00:00', 1),
(3, 'Pemalang', 'Jln. Pemalang', '2025-12-02 17:14:57', 0, '10:00:00', '22:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kategori_menu`
--

CREATE TABLE `kategori_menu` (
  `id` int NOT NULL,
  `cabang_id` int DEFAULT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori_menu`
--

INSERT INTO `kategori_menu` (`id`, `cabang_id`, `nama_kategori`) VALUES
(1, NULL, 'Makanan'),
(2, NULL, 'Minuman'),
(4, 1, 'Jamu'),
(6, 2, 'bakaran');

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id` int NOT NULL,
  `cabang_id` int NOT NULL,
  `nomor_meja` varchar(10) NOT NULL,
  `qr_token` varchar(64) NOT NULL,
  `status` enum('kosong','terisi') DEFAULT 'kosong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`id`, `cabang_id`, `nomor_meja`, `qr_token`, `status`) VALUES
(4, 1, '1', '95bbb0298b19d30a5b92a5f7467dd393', 'terisi'),
(5, 1, '2', '9c1205fb32fab17746bc0a1ff190d750', 'terisi'),
(6, 1, '3', '1d7e2cb1f781072289ac686bb8414cfd', 'terisi'),
(7, 1, '4', 'a0492d83d017f0bb80e39dfaa4efc964', 'terisi'),
(8, 1, '5', 'd62fc4c869418e55dca68cd263fd68e7', 'terisi'),
(9, 1, '6', '2ad827d53711c39bc2fac7c9c2a71154', 'terisi'),
(10, 1, '7', 'c5b533379bb8ce6ca82bd0a37eeb38c5', 'terisi'),
(11, 1, '8', '90209727ae0a672458fd6913597ce6ab', 'terisi'),
(14, 1, '9', '83edace04cfcb1e5173c699c0bfc7bb9', 'terisi'),
(16, 1, '10', '046329c3fae254fc8b84546106c8aeed', 'terisi'),
(27, 1, '11', '98ec5a2ed117976c63cfe74fc8c8951b', 'terisi'),
(28, 1, '12', 'b6f4239c3eb6c2f0b0e74caacc2c426d', 'terisi'),
(29, 1, '13', '463594144b092faf4a5dd172e068f593', 'terisi'),
(30, 1, '14', '72ccc9a5eb4d32255e830ab1da373ffd', 'terisi'),
(31, 1, '15', '06f6c0d99318c418159336c9dd5176af', 'terisi'),
(32, 1, '16', '6f5b086fd99a169970edc11730ee8326', 'kosong'),
(33, 1, '17', '926c4aedbe0aefe2de4d55dec9c0a74f', 'kosong'),
(34, 1, '18', '75d12ec20926161ed955d79436b45526', 'kosong'),
(35, 1, '19', '7d6bac9a87bbd3be5cdfcbc154fcdf39', 'kosong'),
(36, 1, '20', 'd1f34ee8bff7fcfd68ceb5caf96ab739', 'kosong'),
(37, 1, '21', '1b2a9e650e3bcd94cb0d86851b835dca', 'kosong'),
(38, 1, '22', '534d025f4d40a304eac9a17b977a80e8', 'kosong'),
(39, 1, '23', 'ff891540a80a95168b859d811f913cc0', 'kosong'),
(40, 1, '24', '5739248621aa0cd5c5632ca9ede33059', 'kosong'),
(41, 1, '25', '46ba868de27e9131aa13e0282bd9a07e', 'kosong'),
(42, 1, '26', 'bb054b36d2a22bc40cccc63b17428f40', 'kosong'),
(43, 1, '27', '7bf85f47c83d3701c9d7a2350370cfa9', 'kosong'),
(44, 1, '28', 'aff52532f189b08ea7029419e5b1669a', 'kosong'),
(45, 1, '29', '8ca2cfa3bb1d62273b4c7df53a0cd558', 'kosong'),
(46, 1, '30', '5b84ddda19856f4473c6ccbc73282c10', 'kosong'),
(47, 1, '31', '120087bbb2683e77404390234818bf8d', 'kosong'),
(48, 1, '32', '7a5db2033c3f5dc23787d93306f632c3', 'kosong'),
(49, 1, '33', '2ae072747fdec3bb97f786bd8170fb67', 'kosong'),
(50, 1, '34', 'd5825e2185ce1b123a3fe36c574c5eca', 'kosong'),
(51, 1, '35', 'd12768bbccada3ee2f82f75e884730d2', 'kosong'),
(52, 1, '36', 'ddc0056223c3da8d502cbeaefd4475e5', 'kosong'),
(53, 1, '37', 'b1687db56af60f65a14807ca2e585878', 'kosong'),
(54, 1, '38', 'a530fb409d6fe97c139f71508bee18e2', 'kosong'),
(55, 1, '39', 'e5d4ce99d10d45714e97e0b067972262', 'kosong'),
(56, 1, '40', '71feb8791ee3f0cedf0c4ba0b20a4309', 'kosong'),
(57, 1, '41', '827b0ceafd46971291c1953863316b6b', 'kosong'),
(58, 1, '42', '4917d0e6ed4ec0802c7bbb15e0416ba4', 'kosong'),
(59, 1, '43', '991ff7e30f64c842717740b6d5d4c09e', 'kosong'),
(60, 1, '44', '882ebb3a6f0e14fb624bce846e056b3d', 'kosong'),
(61, 1, '45', 'cd2e539c0f882c245aab2116cb635b8f', 'kosong'),
(62, 1, '46', 'decfd81a74cbde53e91eec8da46375d5', 'kosong'),
(63, 1, '47', '688dd2b36b77a2b4390bc81fd7000cd9', 'kosong'),
(64, 1, '48', 'c3a359ea63e965df0d4fcfcacf08e68a', 'kosong'),
(65, 1, '49', 'b0993fca777d5af93ca194aaf32eb520', 'kosong'),
(66, 1, '50', 'a5524d2d770ff6901cb22739e03a55b9', 'kosong'),
(67, 1, '51', '22f29d112daeb6575a4e0fd0cd44fb2c', 'kosong'),
(68, 1, '52', 'aa5bf5b7e169f2825f5f11912eadc642', 'kosong'),
(69, 1, '53', '304a515e4755cb10478ca9eac5003058', 'kosong'),
(70, 1, '54', '5d49ad8d1949da2021d40d363b76b2d2', 'kosong'),
(71, 1, '55', '52c535e19030d04a16ea2555b3012af1', 'kosong'),
(72, 1, '56', '24fd0b6b3ea0cce008e0c83729a5a820', 'kosong'),
(73, 1, '57', 'e86eabb881320dab5b7b3dbdee1d422c', 'kosong'),
(74, 1, '58', '2518dafc52bf34a4884f04442cb388d3', 'kosong'),
(75, 1, '59', '2f7afedb5ffeb797b05e1e41241c2866', 'kosong'),
(76, 1, '60', '6d0c134d8eacceb0394425f898454ece', 'kosong'),
(77, 1, '61', 'a422aabc527079149800e4fdd61140fc', 'kosong'),
(78, 1, '62', '520ba8ebfd8927ad2372e7b10e531b49', 'kosong'),
(79, 1, '63', '3d2f8f77730653bd784182a52edd76ed', 'kosong'),
(80, 1, '64', 'f5f476cbbd5ef8f0e3b52e146506aa24', 'kosong'),
(81, 1, '65', '3e62ce3e547d3d486bdcff79b02e744f', 'kosong'),
(82, 1, '66', 'e68e48863dd93a7142b11db9f2f87956', 'kosong'),
(83, 1, '67', '326e1d9c0e20e8af1e5ba43ff85180a8', 'kosong'),
(84, 1, '68', '6817ed07a9bca3ce53baf2a74fee0ab2', 'kosong'),
(85, 1, '69', '7d7768c158262bb97d78bc470d810ab1', 'kosong'),
(86, 1, '70', '61c180e3337359337370e7464a093310', 'kosong'),
(87, 1, '71', '1e577fce9f536fab86f3770ceb3a1a6f', 'kosong'),
(88, 1, '72', '6f13d308c7584915e39e2facec6fffea', 'kosong'),
(89, 1, '73', 'c584fe22cee4c4fa0643ec779fed269f', 'kosong'),
(90, 1, '74', 'e2f6db42eb3f4a036c17777e9beb6c35', 'kosong'),
(91, 1, '75', 'c022e0908eb61c87b3dfba717c203f26', 'kosong'),
(92, 1, '76', '7498262e30f488878e770f5810fd956c', 'kosong'),
(93, 1, '77', '6e92bf01ccb4231f01cfe6a8518d8dbc', 'kosong'),
(94, 1, '78', '8aa2754d15fb7658a4f7e8eb51588d20', 'kosong'),
(95, 1, '79', '6248108c5c1cada6be45d973425e008d', 'kosong'),
(96, 1, '80', '4afeaee743b9e339b05916f23b6885d6', 'kosong'),
(97, 1, '81', '3647a48c046b82a6178a6d0d60a93fef', 'kosong'),
(98, 1, '82', '30b2a6f54d2d2caedbc916cea72076a2', 'kosong'),
(99, 1, '83', '920b9f5b599a9d22a43dbaa205c1cb04', 'kosong'),
(100, 1, '84', 'a366576e92d8050f7aa5a7192dc66c7f', 'kosong'),
(101, 1, '85', '0e704cb2ecc6d2f0a1cb18d6c233ea23', 'kosong'),
(102, 2, '1', '712781ecd84948931d15c42ff293ecc0', 'terisi'),
(103, 2, '2', '4e0472ef7ae30ef76c2ffd205b667f73', 'terisi'),
(104, 2, '3', 'd414d0fe0312d2318938fe232060ee5b', 'kosong'),
(105, 2, '4', '3ec134961939a89cf29e8394739213bf', 'kosong'),
(106, 2, '5', '144c788aa75853ae1a9423d94a46de37', 'kosong'),
(107, 3, '1', 'f42ca54b732fd56afffd958e00040e92', 'terisi');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int NOT NULL,
  `kategori_id` int NOT NULL,
  `cabang_id` int DEFAULT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `deskripsi` text,
  `harga` decimal(10,2) NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `gambar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `harga_promo` decimal(10,2) DEFAULT '0.00',
  `is_promo` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `kategori_id`, `cabang_id`, `nama_menu`, `deskripsi`, `harga`, `stok`, `gambar`, `is_active`, `harga_promo`, `is_promo`) VALUES
(3, 1, 1, 'temulawak hangat', '', '20000.00', 180, NULL, 1, '0.00', 0),
(4, 4, 1, 'temulawak', 'fff', '23000.00', 172, NULL, 1, '20000.00', 1),
(5, 2, 2, 'es krim', '', '12000.00', 95, NULL, 1, '0.00', 0),
(6, 1, 3, 'ayam goreng pedas', '', '17000.00', 97, NULL, 1, '0.00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE `reservasi` (
  `id` int NOT NULL,
  `uuid` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  `meja_id` int NOT NULL,
  `waktu_reservasi` datetime NOT NULL,
  `durasi_menit` int DEFAULT '45',
  `status` enum('pending','checkin','selesai','batal') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reservasi`
--

INSERT INTO `reservasi` (`id`, `uuid`, `user_id`, `meja_id`, `waktu_reservasi`, `durasi_menit`, `status`, `created_at`) VALUES
(1, 'RES-69340690cfd98', 3, 28, '2025-12-06 18:30:00', 45, 'checkin', '2025-12-06 10:33:52'),
(2, 'RES-693415473dae3', 3, 29, '2025-12-06 19:30:00', 45, 'batal', '2025-12-06 11:36:39'),
(3, 'RES-6934facf3fc91', 3, 32, '2025-12-07 12:00:00', 45, 'checkin', '2025-12-07 03:55:59'),
(4, 'RES-6934fd590afa5', 3, 33, '2025-12-07 12:00:00', 45, 'batal', '2025-12-07 04:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int NOT NULL,
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
  `kembalian` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `uuid`, `user_id`, `meja_id`, `nama_pelanggan`, `total_harga`, `kode_voucher`, `diskon`, `poin_didapat`, `status_pembayaran`, `metode_pembayaran`, `snap_token`, `midtrans_id`, `status_pesanan`, `created_at`, `uang_bayar`, `kembalian`) VALUES
(1, '91660da3-b4b0-4a1d-8bd0-c74ac265b120', NULL, 4, 'kombon', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'selesai', '2025-11-30 15:37:56', '0.00', '0.00'),
(2, '364daa7f-2cd4-4c94-a06f-6bb7889a647d', NULL, 5, 'kombon', '60000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-11-30 15:55:37', '0.00', '0.00'),
(3, '7a69f5f4-77e0-491e-bc8d-43045b88d835', NULL, 4, 'kombon', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-11-30 16:19:48', '0.00', '0.00'),
(4, '27ebcec0-a74a-4b1f-b449-33f5c996e5ef', NULL, 6, 'nenen', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-11-30 16:20:52', '0.00', '0.00'),
(5, '33a11c7a-8f65-4bec-be2f-eec7d7c744c6', NULL, 7, 'kombon', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'selesai', '2025-11-30 16:34:12', '0.00', '0.00'),
(6, 'f52779b7-0535-4233-9c20-acd328f43284', NULL, 7, 'busi', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'selesai', '2025-11-30 17:04:40', '50000.00', '7000.00'),
(7, '5d8ecdff-1588-45e2-ab14-49227f189aa7', NULL, 7, 'ddd', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'selesai', '2025-11-30 17:13:38', '50000.00', '7000.00'),
(8, '3a7c52f4-9a34-464c-bdba-fb73e08185ec', NULL, 7, 'pp', '63000.00', NULL, '0.00', 0, 'pending', 'midtrans', '86a6195e-d640-4563-8a08-90d20f914b2d', NULL, 'menunggu_bayar', '2025-11-30 18:36:05', '0.00', '0.00'),
(9, 'e66c1184-abac-4d5d-bc9e-772a41005843', NULL, 7, 'busi', '46000.00', NULL, '0.00', 0, 'pending', 'midtrans', 'a2616e46-fe4e-480e-bc9e-45a6b5346d0f', NULL, 'menunggu_bayar', '2025-11-30 18:36:35', '0.00', '0.00'),
(10, 'e1cf5c19-5463-40f1-9211-5f3663d0c749', NULL, 7, 'busi', '43000.00', NULL, '0.00', 0, 'pending', 'midtrans', 'a8c28e99-5c93-4e21-8c19-452a221fcb3c', NULL, 'menunggu_bayar', '2025-11-30 18:43:36', '0.00', '0.00'),
(11, 'bef2ad5d-2e18-43b0-b601-95a7796fe531', NULL, 7, 'busi', '43000.00', NULL, '0.00', 0, 'pending', 'midtrans', '4a9f22d9-952f-4a81-b158-30464a60029c', NULL, 'menunggu_bayar', '2025-11-30 18:43:46', '0.00', '0.00'),
(12, '71ac3fb8-2f4f-4228-a7c2-e1351d63ef8f', NULL, 7, 'pp', '63000.00', NULL, '0.00', 0, 'pending', 'midtrans', 'a4db5fc6-77eb-4bbb-a25e-c4e91e8d2b65', NULL, 'menunggu_bayar', '2025-11-30 18:53:19', '0.00', '0.00'),
(13, 'e004a77f-0162-434b-8951-4ad59e473ebc', NULL, 7, 'pp', '43000.00', NULL, '0.00', 0, 'pending', 'midtrans', 'c955d64d-c371-46d2-ae28-34594f89c6ed', NULL, 'menunggu_bayar', '2025-11-30 18:58:08', '0.00', '0.00'),
(14, 'cead9227-fee2-4186-a457-c8f5b73dfa50', NULL, 7, 'pp', '43000.00', NULL, '0.00', 0, 'pending', 'midtrans', '95ee881c-c20d-47ed-b70c-b3247f12f49a', NULL, 'menunggu_bayar', '2025-11-30 18:58:15', '0.00', '0.00'),
(15, '5566e8cf-cf5b-4c3c-b3e0-182dfe37bc41', NULL, 7, 'pp', '43000.00', NULL, '0.00', 0, 'pending', 'midtrans', '17e69449-0015-4274-ab0c-953f0937dbef', NULL, 'menunggu_bayar', '2025-11-30 18:58:30', '0.00', '0.00'),
(16, '967e1821-1273-4a5e-9de3-00fdeb24843c', NULL, 16, 'nanang', '100000.00', NULL, '0.00', 0, 'pending', 'midtrans', '491441f0-0d83-4f42-99a9-9b69a412a528', NULL, 'menunggu_bayar', '2025-11-30 19:00:08', '0.00', '0.00'),
(17, '26e232b9-5a28-4977-a461-90bf7fdf7420', NULL, 16, 'nanang', '100000.00', NULL, '0.00', 0, 'pending', 'midtrans', '2393862b-7d01-4ea5-b833-1673e15cd471', NULL, 'menunggu_bayar', '2025-11-30 19:00:27', '0.00', '0.00'),
(18, 'fd6000d5-134a-4f9e-895e-d28f4ae947d7', NULL, 16, 'nanang', '100000.00', NULL, '0.00', 0, 'pending', 'midtrans', '64192fa0-0620-4730-b718-85b728920513', NULL, 'menunggu_bayar', '2025-11-30 19:01:08', '0.00', '0.00'),
(19, 'c424ee41-cc05-4c0a-996a-341e81bc948b', NULL, 7, 'ddawqe', '103000.00', NULL, '0.00', 0, 'pending', 'midtrans', '2b77b0ef-5155-4c6f-88f8-2301ae181811', NULL, 'menunggu_bayar', '2025-11-30 19:01:36', '0.00', '0.00'),
(20, '59edeb34-f6d7-4d63-8e6c-785c79f4fb17', NULL, 7, 'busi', '46000.00', NULL, '0.00', 0, 'pending', 'midtrans', '5e2cb558-ffd6-4077-9058-7fd1917ff626', NULL, 'menunggu_bayar', '2025-11-30 19:10:30', '0.00', '0.00'),
(21, '7dfbb186-3acf-402f-a3a1-67d0bb3aeed7', NULL, 10, 'nanang', '160000.00', NULL, '0.00', 0, 'pending', 'midtrans', '0a46928e-c54b-4846-96ba-71f7ab4f1f52', NULL, 'menunggu_bayar', '2025-11-30 19:23:15', '0.00', '0.00'),
(22, 'd6f59ad9-5fbe-4fca-af26-6e4da032357e', NULL, 7, 'ddawqe', '43000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-11-30 19:25:27', '50000.00', '7000.00'),
(23, '6de4e4ad-56b2-446f-8491-bf9e06037864', NULL, 7, 'budi', '198000.00', NULL, '0.00', 0, 'settlement', 'midtrans', 'c60f4d17-fff2-40b1-8d49-f42d77d648ca', 'RESTO-6de4e4ad-1764531706', 'diproses', '2025-11-30 19:41:47', '0.00', '0.00'),
(24, 'c15f5e42-d44f-4f54-bf95-b157606b8c91', NULL, 14, 'ddawqe', '103000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-01 07:14:40', '110000.00', '7000.00'),
(25, 'c8d0dad0-a0a7-4c89-a8c3-98aec5ca060c', NULL, 11, 'pp', '126000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-01 07:48:26', '150000.00', '24000.00'),
(26, '9af94ef8-da68-4cb8-bd6b-3202dca4c5b4', NULL, 8, 'nanang', '83000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-01 12:14:56', '85000.00', '2000.00'),
(27, 'e3d201a4-9b04-4a2c-9ba7-278a7387239d', NULL, 9, 'nanang', '253000.00', NULL, '0.00', 0, 'cancel', 'midtrans', '850540aa-7751-49fe-9bc7-0e8b3187d6a0', 'RESTO-e3d201a4-1764593075', 'menunggu_bayar', '2025-12-01 12:44:36', '0.00', '0.00'),
(28, '2313972f-0f14-4aa5-af47-263902cbd637', NULL, 27, 'wewe', '46000.00', NULL, '0.00', 0, 'pending', 'tunai', NULL, NULL, 'menunggu_konfirmasi', '2025-12-02 13:59:49', '0.00', '0.00'),
(29, '85d1224b-c2e9-4222-a20f-f24ddbd5d998', NULL, 102, 'kontrol', '60000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-02 16:05:30', '70000.00', '10000.00'),
(30, '05dc0121-3401-4bb1-b7e5-666e286a8c74', NULL, 103, 'nanang', '60000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-02 17:12:41', '60000.00', '0.00'),
(31, '3b457bf2-8c3a-46c0-a8a3-24eb9017009d', NULL, 107, 'nanang', '51000.00', NULL, '0.00', 0, 'settlement', 'midtrans', '96bcd5aa-6363-42b6-8af9-d8770ce45166', 'RESTO-3b457bf2-1764697943', 'diproses', '2025-12-02 17:52:23', '0.00', '0.00'),
(32, 'e8e3c992-5112-429c-8e2f-4f0e99196645', NULL, 29, 'ddd', '40000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-06 15:58:00', '50000.00', '10000.00'),
(33, 'b5d1d344-dc4f-40e7-b0e1-5d7c893f6115', NULL, 28, 'nanang', '40000.00', NULL, '0.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-06 16:05:16', '50000.00', '10000.00'),
(34, '4c43352a-c134-4bbe-9fde-9a3c49791c2c', 3, 28, 'kombon', '36000.00', 'murah', '4000.00', 3, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-07 02:14:55', '50000.00', '14000.00'),
(35, 'TRX-6934ec55f0d1c', NULL, 31, 'pp', '38700.00', 'MURAH', '4300.00', 0, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-07 02:54:13', '40000.00', '1300.00'),
(36, 'e8c3ad0d-9c2e-45ce-bd55-f08d161eba5d', 3, 28, 'kombon', '113400.00', 'MURAH', '12600.00', 11, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-07 03:17:36', '120000.00', '6600.00'),
(37, '74dadbe9-6f3c-4e68-8896-2a0325db5706', 3, 28, 'kombon', '40000.00', NULL, '0.00', 4, 'settlement', 'tunai', NULL, NULL, 'diproses', '2025-12-07 03:19:25', '50000.00', '10000.00'),
(38, '41ceb3ee-2c53-40ea-bf69-42d2be6d64ee', 3, 28, 'kombon', '54000.00', 'MURAH', '6000.00', 5, 'cancel', 'midtrans', '7e1485aa-0e49-4f28-8578-2e4c4b293fa3', 'PENJUALAN-251207371', 'menunggu_bayar', '2025-12-07 03:23:43', '0.00', '0.00'),
(39, '78963d41-fee2-44e4-9d70-c2bfe2a10802', 3, 28, 'kombon', '40000.00', NULL, '0.00', 4, 'settlement', 'midtrans', '94370731-c540-4339-997a-1575e82e3997', 'PENJUALAN-251207460', 'diproses', '2025-12-07 03:36:59', '0.00', '0.00'),
(40, '79daf21d-1b59-4c8b-ba0b-aa67bedd3827', 3, 28, 'kombon', '40000.00', NULL, '0.00', 4, 'settlement', 'midtrans', '97ae0d8f-eb9f-4330-ad55-33bb98e7e524', 'PENJUALAN-251207571', 'selesai', '2025-12-07 03:40:32', '0.00', '0.00'),
(41, 'e2079b4d-90b2-498b-936a-5d78690348b0', 3, 28, 'kombon', '80000.00', NULL, '0.00', 8, 'pending', 'midtrans', 'bd68fff4-9056-41c9-a5be-d3cf9e0ded3c', 'PENJUALAN-251207651', 'menunggu_bayar', '2025-12-07 03:43:13', '0.00', '0.00'),
(42, 'TRX-6934f9fc22329', NULL, 30, 'kombon', '36000.00', 'MURAH', '4000.00', 0, 'pending', 'midtrans', '13eec99a-20a6-4fbd-8cef-ea1fd0455345', NULL, 'menunggu_bayar', '2025-12-07 03:52:29', '0.00', '-36000.00'),
(43, '3ac51e5c-3ead-499f-ba39-7703ef3bb14b', 3, 28, 'kombon', '54000.00', 'MURAH', '6000.00', 5, 'settlement', 'midtrans', 'ae7564d0-7fa3-4bfd-bbfb-dfd5bc48aab8', 'PENJUALAN-251207924', 'selesai', '2025-12-07 03:53:09', '0.00', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int NOT NULL,
  `transaksi_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `qty` int NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `catatan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi_detail`
--

INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `menu_id`, `qty`, `harga_satuan`, `subtotal`, `catatan`) VALUES
(1, 1, 4, 1, '23000.00', '23000.00', NULL),
(2, 1, 3, 1, '20000.00', '20000.00', NULL),
(3, 2, 3, 3, '20000.00', '60000.00', NULL),
(4, 3, 4, 1, '23000.00', '23000.00', NULL),
(5, 3, 3, 1, '20000.00', '20000.00', NULL),
(6, 4, 4, 1, '23000.00', '23000.00', NULL),
(7, 4, 3, 1, '20000.00', '20000.00', NULL),
(8, 5, 3, 1, '20000.00', '20000.00', NULL),
(9, 5, 4, 1, '23000.00', '23000.00', NULL),
(10, 6, 3, 1, '20000.00', '20000.00', NULL),
(11, 6, 4, 1, '23000.00', '23000.00', NULL),
(12, 7, 4, 1, '23000.00', '23000.00', NULL),
(13, 7, 3, 1, '20000.00', '20000.00', NULL),
(14, 8, 3, 2, '20000.00', '40000.00', NULL),
(15, 8, 4, 1, '23000.00', '23000.00', NULL),
(16, 9, 4, 2, '23000.00', '46000.00', NULL),
(17, 10, 4, 1, '23000.00', '23000.00', NULL),
(18, 10, 3, 1, '20000.00', '20000.00', NULL),
(19, 11, 4, 1, '23000.00', '23000.00', NULL),
(20, 11, 3, 1, '20000.00', '20000.00', NULL),
(21, 12, 3, 2, '20000.00', '40000.00', NULL),
(22, 12, 4, 1, '23000.00', '23000.00', NULL),
(23, 13, 4, 1, '23000.00', '23000.00', NULL),
(24, 13, 3, 1, '20000.00', '20000.00', NULL),
(25, 14, 4, 1, '23000.00', '23000.00', NULL),
(26, 14, 3, 1, '20000.00', '20000.00', NULL),
(27, 15, 4, 1, '23000.00', '23000.00', NULL),
(28, 15, 3, 1, '20000.00', '20000.00', NULL),
(29, 16, 3, 5, '20000.00', '100000.00', NULL),
(30, 17, 3, 5, '20000.00', '100000.00', NULL),
(31, 18, 3, 5, '20000.00', '100000.00', NULL),
(32, 19, 4, 1, '23000.00', '23000.00', NULL),
(33, 19, 3, 4, '20000.00', '80000.00', NULL),
(34, 20, 4, 2, '23000.00', '46000.00', NULL),
(35, 21, 3, 8, '20000.00', '160000.00', NULL),
(36, 22, 3, 1, '20000.00', '20000.00', NULL),
(37, 22, 4, 1, '23000.00', '23000.00', NULL),
(38, 23, 3, 3, '20000.00', '60000.00', NULL),
(39, 23, 4, 6, '23000.00', '138000.00', NULL),
(40, 24, 3, 4, '20000.00', '80000.00', NULL),
(41, 24, 4, 1, '23000.00', '23000.00', NULL),
(42, 25, 3, 4, '20000.00', '80000.00', NULL),
(43, 25, 4, 2, '23000.00', '46000.00', NULL),
(44, 26, 3, 3, '20000.00', '60000.00', NULL),
(45, 26, 4, 1, '23000.00', '23000.00', NULL),
(46, 27, 4, 11, '23000.00', '253000.00', NULL),
(47, 28, 4, 2, '23000.00', '46000.00', NULL),
(48, 29, 5, 5, '12000.00', '60000.00', NULL),
(49, 30, 5, 5, '12000.00', '60000.00', NULL),
(50, 31, 6, 3, '17000.00', '51000.00', NULL),
(51, 32, 4, 1, '20000.00', '20000.00', NULL),
(52, 32, 3, 1, '20000.00', '20000.00', NULL),
(53, 33, 3, 1, '20000.00', '20000.00', NULL),
(54, 33, 4, 1, '20000.00', '20000.00', NULL),
(55, 34, 4, 1, '20000.00', '20000.00', NULL),
(56, 34, 3, 1, '20000.00', '20000.00', NULL),
(57, 35, 4, 1, '23000.00', '23000.00', NULL),
(58, 35, 3, 1, '20000.00', '20000.00', NULL),
(59, 36, 3, 4, '20000.00', '80000.00', NULL),
(60, 36, 4, 2, '23000.00', '46000.00', NULL),
(61, 37, 4, 1, '20000.00', '20000.00', NULL),
(62, 37, 3, 1, '20000.00', '20000.00', NULL),
(63, 38, 4, 2, '20000.00', '40000.00', NULL),
(64, 38, 3, 1, '20000.00', '20000.00', NULL),
(65, 39, 4, 1, '20000.00', '20000.00', NULL),
(66, 39, 3, 1, '20000.00', '20000.00', NULL),
(67, 40, 4, 2, '20000.00', '40000.00', NULL),
(68, 41, 3, 4, '20000.00', '80000.00', NULL),
(69, 42, 4, 1, '20000.00', '20000.00', NULL),
(70, 42, 3, 1, '20000.00', '20000.00', NULL),
(71, 43, 4, 2, '20000.00', '40000.00', NULL),
(72, 43, 3, 1, '20000.00', '20000.00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cabang_id` int DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `level` enum('admin','karyawan','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL,
  `poin` int DEFAULT '0',
  `foto` text,
  `alamat` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `nama`, `email`, `password`, `cabang_id`, `no_hp`, `level`, `created_at`, `last_active`, `poin`, `foto`, `alamat`) VALUES
(1, '103201662146363136452', 'Admin', 'ageng.prayoga321@gmail.com', '$2y$10$NgJ8piqxSuqJ5LuqqKsWUeAvNS74iX6j/tsI1IfR5iFw65CXRiUUu', NULL, '090909', 'admin', '2025-11-26 15:35:02', '2025-12-07 13:41:19', 0, 'https://lh3.googleusercontent.com/a/ACg8ocK5DWAgbhYERk_y4t1El2wRicJzO2sfSnWvOgvkEG2E79LimMMe=s96-c', ''),
(3, '108883213726644898828', 'kombon', 'kombonmbon45@gmail.com', '$2y$10$6Xy69SAKEKtRSwa50ke3u.ydoPEEjmHTbUXlOHbnEXDZN/18HTkVK', 1, '0800', 'pelanggan', '2025-11-26 17:07:33', '2025-12-07 08:51:06', 13, 'https://lh3.googleusercontent.com/a/ACg8ocLB4Co0CMMUgzOknnIHtCmb8WxCHRIVhyn1ztvqmcAzafPLlM4=s96-c', NULL),
(5, '107164099205175610272', 'Doni', 'aylalopyta21@gmail.com', '$2y$10$JdqV6s10HXVl/yh6bDCz6.xLoMoQz/B4a.nkoekOhH.7B/CNPwNYS', 1, NULL, 'karyawan', '2025-12-02 17:50:23', '2025-12-07 00:18:44', 0, 'https://lh3.googleusercontent.com/a-/ALV-UjVA81ZQOzGfXMHNlO4qSW1gb4tDJrsyJm8kaQpIVDn4GuN3MPxFgmedysS86lhcZ7CF6qlA41hdC_OKoASk3RVTVG3sEQpcXCenIzu3GwvwEP0ZXa8-jvfyC-JUmXBcC1_bg1r5TIW1VLWGyevnXMBwY3gQjl9NNQC_26cc4g1CI-3JpkDcTCgl45GKrnlr1wziYevLF9-jnRLN9sFc2HHJI2KKPIYGMK25OiAhHI34MHHOzghEF6_fhLCnK6QThP_HUUdOA55iGykY4ERnzn9FD8kn9KN16K8JqDFq3BGpIHxxpXatPEDFD-wEYtoktBMq99H4uZSLgwZqvhKsOD3Rfc7k8anO9ammrEMO_1l2CSqUH4KP0hreioDn9HxBSeG3W7tQTZWRBIeQD3DD8Lv5KMFrEgvCSqgMpMRZmbOZ3qCjEo8DuGZqxgyLSF_yno1_VuJMbWDOYbf84l_wVY3DsdGIGPW6ocpP60oLq7Jz4xpcq3NQC1dMnM5jyr7ZdweDjjwtXDNjbsB_HHCumkrFb_IXByDav9Gcw-fbovuhH4yloyMrbWh4tV8MkqESkfg898OB2epG03LbJ_P0AfyJ0BiQtiy35aWN8b2U8RDGMnfnSG276_Pau2WxC6SmFY8Fft-kaoI30Mo7KaIt9m8c4qR7y9zrOs_y3kmvudLZmRQr40rG9qOk1EaXh9JRTu-3alPefKTnLnlleisFs2xeX92-hY6T4Ih_htK5fpyQyBc8eCnImHNAj7iMZWINBdCpFYSyFCgXvJs6m9ehruvvfdSR0yamlwaNG0XKAufG8J7NqVKLci4HTE1958e0-l0SblczRJTFE_zR3gNL5Uj3ljkzJbZ6PVGxsvQXVMrNR54YkvrdrwcZeS35UW2STMKexDrP3PYJZyIzX5lmI-REEHgtzmuLWIi3g6xYBUpXMiGqOzmxyzxPrwrLO-bTx8avylvfj1-ow8CDDE3X6vpzpmeLRFCZIPNan7hGQXEG_xoo9zLLzyc8LYM2Oqn1g7eBbafIUJ7O1jWLG31vv0MzG6Kx2Sc4HCrZPAqVOSBhSOJfpUWuI38=s96-c', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int NOT NULL,
  `kode` varchar(50) NOT NULL,
  `tipe` enum('fixed','percent') DEFAULT 'fixed',
  `nilai` decimal(15,2) NOT NULL,
  `min_belanja` decimal(15,2) DEFAULT '0.00',
  `stok` int DEFAULT '100',
  `berlaku_sampai` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `kode`, `tipe`, `nilai`, `min_belanja`, `stok`, `berlaku_sampai`, `created_at`) VALUES
(1, 'MURAH', 'percent', '10.00', '0.00', 94, '2025-12-07', '2025-12-07 02:14:41');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kategori_menu`
--
ALTER TABLE `kategori_menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservasi`
--
ALTER TABLE `reservasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
