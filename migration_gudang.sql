-- 1. Update ENUM level di tabel users untuk support 'gudang'
ALTER TABLE users MODIFY COLUMN level ENUM('admin', 'karyawan', 'pelanggan', 'gudang') NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_module VARCHAR(50) DEFAULT NULL AFTER last_active;

-- 2. Tabel Master Barang Gudang (Bahan Baku & Produk Jadi)
CREATE TABLE IF NOT EXISTS `gudang_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `satuan` varchar(20) NOT NULL COMMENT 'e.g., kg, liter, pcs',
  `stok` int(11) DEFAULT 0,
  `jenis` enum('bahan_baku','produk_jadi') NOT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Request Stok (Permintaan dari Cabang ke Gudang)
CREATE TABLE IF NOT EXISTS `request_stok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_request` varchar(50) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','diproses','dikirim','selesai','batal') DEFAULT 'pending',
  `catatan_cabang` text,
  `catatan_gudang` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `tanggal_kirim` datetime DEFAULT NULL,
  `tanggal_terima` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Detail Item Request
CREATE TABLE IF NOT EXISTS `request_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty_minta` int(11) NOT NULL,
  `qty_kirim` int(11) DEFAULT 0,
  `qty_terima` int(11) DEFAULT 0,
  `status_item` enum('sesuai','kurang','lebih') DEFAULT 'sesuai',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`request_id`) REFERENCES `request_stok` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `gudang_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Mutasi Gudang (Log Masuk/Keluar Internal Gudang)
CREATE TABLE IF NOT EXISTS `mutasi_gudang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `jenis_mutasi` enum('masuk_supplier','keluar_produksi','masuk_produksi','keluar_cabang','buang/rusak') NOT NULL,
  `qty` int(11) NOT NULL,
  `keterangan` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
