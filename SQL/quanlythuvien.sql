-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 17, 2026 lúc 04:48 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

CREATE DATABASE IF NOT EXISTS `quanlythuvien`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `quanlythuvien`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanlythuvien`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `doc_gia`
--

CREATE TABLE `doc_gia` (
  `id_doc_gia` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` varchar(10) DEFAULT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `doc_gia`
--

INSERT INTO `doc_gia` (`id_doc_gia`, `ho_ten`, `ngay_sinh`, `gioi_tinh`, `so_dien_thoai`, `dia_chi`) VALUES
(1, 'Nguyễn Văn An', '2007-03-15', 'Nam', '0912345678', 'Hà Nội'),
(2, 'Trần Minh Anh', '2006-07-22', 'Nữ', '0987654321', 'Hà Nội'),
(3, 'Lê Hoàng Nam', '2007-01-10', 'Nam', '0901234567', 'Ninh Bình'),
(4, 'Phạm Thu Hà', '2006-11-05', 'Nữ', '0934567890', 'Bắc Ninh'),
(5, 'Đỗ Minh Quân', '2007-08-18', 'Nam', '0978123456', 'Thanh Hóa');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `muon_tra`
--

CREATE TABLE `muon_tra` (
  `id_muon_tra` int(11) NOT NULL,
  `id_sach` int(11) NOT NULL,
  `id_doc_gia` int(11) NOT NULL,
  `ngay_muon` date NOT NULL,
  `ngay_tra` date DEFAULT NULL,
  `trang_thai` varchar(30) DEFAULT 'Đang mượn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `muon_tra`
--

INSERT INTO `muon_tra` (`id_muon_tra`, `id_sach`, `id_doc_gia`, `ngay_muon`, `ngay_tra`, `trang_thai`) VALUES
(11, 1, 1, '2026-08-10', '2026-08-17', 'Đã trả'),
(12, 2, 4, '2026-08-12', NULL, 'Đang mượn'),
(13, 3, 2, '2026-08-13', '2026-08-16', 'Đã trả'),
(14, 4, 4, '2026-08-15', NULL, 'Đang mượn'),
(15, 5, 3, '2026-08-16', NULL, 'Đang mượn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyensach`
--

CREATE TABLE `quyensach` (
  `id_sach` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `tac_gia` varchar(255) NOT NULL,
  `the_loai` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quyensach`
--

INSERT INTO `quyensach` (`id_sach`, `name`, `img`, `tac_gia`, `the_loai`) VALUES
(1, 'Có hai con mèo ngồi bên cửa sổ', 'Có hai con mèo ngồi bên cửa sổ.jpg', 'Nguyễn Nhật Ánh', 'Đồng thoại'),
(2, 'Hồ Điệp và Kình Ngư', 'ho-diep-va-kinh-ngu_17307_1.jpg', 'Tuế Kiến', 'Tiểu thuyết'),
(3, 'Làm bạn với bầu trời', '(Pdf) Làm bạn với bầu trời - Nguyễn Nhật Ánh.jpg', 'Nguyễn Nhật Ánh', 'Truyện dài'),
(4, 'Harry Potter và hòn đá phù thủy', 'harry-potter-1.jpg', 'J.K.Rowling', 'Fantasy'),
(5, 'Tuổi trẻ đáng giá bao nhiêu?', 'tuoi-tre-dang-gia-bao-nhieu-rosie-nguyen.jpg', 'Rosie Nguyễn', 'Kỹ năng sống');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `doc_gia`
--
ALTER TABLE `doc_gia`
  ADD PRIMARY KEY (`id_doc_gia`);

--
-- Chỉ mục cho bảng `muon_tra`
--
ALTER TABLE `muon_tra`
  ADD PRIMARY KEY (`id_muon_tra`),
  ADD KEY `fk_muontra_sach` (`id_sach`),
  ADD KEY `fk_muontra_docgia` (`id_doc_gia`);

--
-- Chỉ mục cho bảng `quyensach`
--
ALTER TABLE `quyensach`
  ADD PRIMARY KEY (`id_sach`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `doc_gia`
--
ALTER TABLE `doc_gia`
  MODIFY `id_doc_gia` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `muon_tra`
--
ALTER TABLE `muon_tra`
  MODIFY `id_muon_tra` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `quyensach`
--
ALTER TABLE `quyensach`
  MODIFY `id_sach` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `muon_tra`
--
ALTER TABLE `muon_tra`
  ADD CONSTRAINT `fk_muontra_docgia` FOREIGN KEY (`id_doc_gia`) REFERENCES `doc_gia` (`id_doc_gia`),
  ADD CONSTRAINT `fk_muontra_sach` FOREIGN KEY (`id_sach`) REFERENCES `quyensach` (`id_sach`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
