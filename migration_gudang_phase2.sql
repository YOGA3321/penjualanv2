-- 1. Tabel Pemasok / Supplier
CREATE TABLE IF NOT EXISTS `pemasok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_pemasok` varchar(100) NOT NULL,
  `kontak` varchar(50),
  `alamat` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Transaksi Barang Masuk (Header)
CREATE TABLE IF NOT EXISTS `barang_masuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pemasok_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL, -- Staff Gudang yang input
  `tanggal_masuk` datetime DEFAULT CURRENT_TIMESTAMP,
  `bukti_nota` varchar(255), -- Foto/Scan Nota
  `keterangan` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`pemasok_id`) REFERENCES `pemasok` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Detail Barang Masuk
CREATE TABLE IF NOT EXISTS `barang_masuk_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barang_masuk_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_beli` decimal(15,2) DEFAULT 0, -- Opsional: untuk pencatatan HPP
  PRIMARY KEY (`id`),
  FOREIGN KEY (`barang_masuk_id`) REFERENCES `barang_masuk` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `gudang_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
