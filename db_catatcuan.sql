-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 05, 2026 at 09:47 PM
-- Server version: 9.5.0
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_catatcuan`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_kategori`
--

CREATE TABLE `tb_kategori` (
  `id_kategori` int NOT NULL,
  `id_user` int DEFAULT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `jenis_arus` enum('masuk','keluar') NOT NULL,
  `ikon` varchar(10) DEFAULT 0xF09F92B0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_kategori`
--

INSERT INTO `tb_kategori` (`id_kategori`, `id_user`, `nama_kategori`, `jenis_arus`, `ikon`) VALUES
(1, NULL, 'Penjualan Produk', 'masuk', '💰'),
(2, NULL, 'Ongkos Kirim', 'masuk', '🛵'),
(3, NULL, 'Pembelian Stok', 'keluar', '🛒'),
(4, NULL, 'Biaya Operasional', 'keluar', '⚡'),
(5, NULL, 'Jasa/Layanan', 'masuk', '🛠️'),
(6, 3, 'Penjualan Produk', 'masuk', '💰'),
(7, 3, 'Pendapatan Lain-lain', 'masuk', '📥'),
(8, 3, 'Biaya Operasional', 'keluar', '⚙️'),
(9, 3, 'Pembelian Stok', 'keluar', '📦'),
(10, 3, 'Biaya Pengiriman', 'keluar', '🚚'),
(11, 3, 'Gaji Karyawan', 'keluar', '👥'),
(12, 4, 'Penjualan Produk', 'masuk', '💰'),
(13, 4, 'Pendapatan Lain-lain', 'masuk', '📥'),
(14, 4, 'Biaya Operasional', 'keluar', '⚙️'),
(15, 4, 'Pembelian Stok', 'keluar', '📦'),
(16, 4, 'Biaya Pengiriman', 'keluar', '🚚'),
(17, 4, 'Gaji Karyawan', 'keluar', '👥');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengguna`
--

CREATE TABLE `tb_pengguna` (
  `id_user` int NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_pengguna`
--

INSERT INTO `tb_pengguna` (`id_user`, `nama_toko`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'CatatCuan', 'admin@catatcuan.com', '$2y$10$G7BM5Ebil3B.uf3hFC/idekTqJfl3y90sHFzZEqER1c1guprCE9qK', 'admin', '2026-04-20 17:34:25'),
(2, 'Toko Maju Jaya', 'user@catatcuan.com', '$2y$10$gP682YCR8KRJU3HN/97DledITUOJFw3F7SpWQLKuYG1Lhk1lYadR2', 'user', '2026-04-27 04:57:06'),
(3, 'coffeshop', 'kopijaya@gmail.com', '$2y$10$.4Osasp5YYS6kPmWE2HVWOVcjoGksWKLlpwuTRZiqUgJXlYI2n3MC', 'user', '2026-04-27 18:20:16'),
(4, 'Atk Pintar Sejahtera', 'printer@gmail.com', '$2y$10$gFbyAi4hCHt2/pa5XELAYOZevhpK0wVdc7EJns3HLJcDCWg4gqq56', 'user', '2026-04-27 18:22:52');

-- --------------------------------------------------------

--
-- Table structure for table `tb_produk`
--

CREATE TABLE `tb_produk` (
  `id_produk` int NOT NULL,
  `id_user` int NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga_beli` int NOT NULL DEFAULT '0',
  `harga_jual` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_produk`
--

INSERT INTO `tb_produk` (`id_produk`, `id_user`, `nama_produk`, `harga_beli`, `harga_jual`, `created_at`) VALUES
(1, 1, 'Beras Premium 5Kg', 62000, 75000, '2026-04-27 04:00:38'),
(2, 1, 'Minyak Goreng 2L', 28500, 34000, '2026-04-27 04:01:05'),
(3, 1, 'Telur Ayam (1 Rak)', 48000, 55000, '2026-04-27 04:01:30'),
(4, 1, 'Gula Pasir 1Kg', 14200, 17500, '2026-04-27 04:01:52'),
(5, 1, 'Kopi Bubuk Lokal', 22000, 35000, '2026-04-27 04:02:10'),
(6, 1, 'Sabun Cuci Piring 700ml', 13500, 16500, '2026-04-27 05:45:17'),
(7, 1, 'Kecap Manis Botol 550ml', 19800, 24000, '2026-04-27 05:45:42'),
(8, 1, 'Susu Kaleng Kental Manis', 11200, 13500, '2026-04-27 05:46:03'),
(9, 1, 'Tepung Terigu 1kg', 10500, 12500, '2026-04-27 05:46:24'),
(10, 1, 'Gas Elpiji 3kg (isi ulang)', 18000, 22000, '2026-04-27 05:46:55'),
(11, 4, 'Kertas A4 70gr', 45000, 55000, '2026-04-27 18:23:54'),
(12, 4, 'Bolpoin Pack', 18000, 25000, '2026-04-27 18:24:16'),
(13, 4, 'Tinta Printer', 85000, 105000, '2026-04-27 18:24:39'),
(14, 3, 'Kopi Bubuk Lokal 250g', 22000, 35000, '2026-04-27 18:26:34'),
(15, 3, 'Susu Kaleng Kental Manis', 11200, 13500, '2026-04-27 18:26:53'),
(16, 3, 'Sirup Vanilla', 65000, 85000, '2026-04-27 18:27:15'),
(17, 3, 'Sirup Vanilla 200ml', 80000, 85000, '2026-05-05 06:35:13');

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_transaksi` int NOT NULL,
  `id_user` int NOT NULL,
  `id_kategori` int NOT NULL,
  `id_produk` int DEFAULT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `nominal` int NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_transaksi`, `id_user`, `id_kategori`, `id_produk`, `jenis`, `tanggal`, `waktu`, `nominal`, `keterangan`, `catatan`, `created_at`) VALUES
(9, 1, 1, 3, 'masuk', '2026-04-27', '04:02:00', 55000, 'Penjualan Telur ayam 1 rak', '', '2026-04-27 04:03:33'),
(10, 1, 3, 1, 'keluar', '2026-04-27', '04:06:00', 62000, 'Restock beras 5kg', '', '2026-04-27 04:09:00'),
(11, 1, 1, 5, 'masuk', '2026-04-27', '04:09:00', 35000, 'penjualan kopi', '', '2026-04-27 04:09:59'),
(12, 1, 1, 9, 'masuk', '2026-04-27', '05:47:00', 12500, 'penjualan terigu', '', '2026-04-27 05:47:38'),
(13, 1, 3, 10, 'keluar', '2026-04-27', '05:48:00', 90000, 'Pembelian isi ulang 5 tabung Gas Elpiji 3kg', '', '2026-04-27 05:49:15'),
(14, 1, 3, 8, 'keluar', '2026-04-27', '05:49:00', 22400, 'Kulakan 2 dus Susu Kaleng Kental Manis', '', '2026-04-27 05:49:48'),
(15, 3, 6, 14, 'masuk', '2026-04-27', '18:27:00', 35000, 'Penjualan bubuk kopi', '', '2026-04-27 18:28:40'),
(16, 3, 9, 16, 'keluar', '2026-04-27', '18:28:00', 65000, 'restock sirup vanilla', '', '2026-04-27 18:29:06'),
(17, 3, 6, 16, 'masuk', '2026-05-05', '06:25:00', 85000, 'penjualan sirup rasa vanilla', '', '2026-05-05 06:25:41'),
(18, 3, 9, 15, 'keluar', '2026-05-05', '06:25:00', 11200, 'restock susu kaleng', '', '2026-05-05 06:26:13'),
(19, 3, 9, 17, 'keluar', '2026-05-05', '06:35:00', 85000, 'pembelian sirup vanilla 200ml', '', '2026-05-05 06:36:21'),
(20, 3, 9, 17, 'keluar', '2026-05-05', '06:37:00', 80000, 'pembelian sirup vanilla 200ml', '', '2026-05-05 06:37:14'),
(21, 3, 9, 17, 'keluar', '2026-05-05', '06:42:00', 80000, 'pembelian sirup vanilla 200ml', '', '2026-05-05 06:43:06'),
(22, 3, 6, 16, 'masuk', '2026-05-18', '15:55:00', 85000, '', '', '2026-05-18 15:56:07'),
(23, 3, 6, 14, 'masuk', '2026-05-18', '15:56:00', 35000, '', '', '2026-05-18 15:56:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD KEY `fk_kategori_user` (`id_user`);

--
-- Indexes for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `fk_produk_user` (`id_user`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kategori` (`id_kategori`),
  ADD KEY `fk_transaksi_produk` (`id_produk`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_produk`
--
ALTER TABLE `tb_produk`
  MODIFY `id_produk` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD CONSTRAINT `fk_kategori_user` FOREIGN KEY (`id_user`) REFERENCES `tb_pengguna` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD CONSTRAINT `fk_produk_user` FOREIGN KEY (`id_user`) REFERENCES `tb_pengguna` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `fk_transaksi_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE SET NULL,
  ADD CONSTRAINT `tb_transaksi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_pengguna` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_transaksi_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `tb_kategori` (`id_kategori`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
