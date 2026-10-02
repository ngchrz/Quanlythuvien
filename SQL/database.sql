-- =====================================================
-- QUAN LY THU VIEN — FILE SQL DUY NHAT (gop tu 3 file)
-- Import 1 lan trong phpMyAdmin (tab Import) khi cai dat moi.
-- Gom: database + 3 bang goc (quyensach, doc_gia, muon_tra)
--      + du lieu mau + bang users (role admin/user)
--      + tai khoan admin mac dinh: admin / admin123
-- Luu y: 3 bang goc va du lieu goc duoc GIU NGUYEN 100%.
-- =====================================================

CREATE DATABASE IF NOT EXISTS `quanlythuvien`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `quanlythuvien`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Bang `doc_gia` (GIU NGUYEN)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `doc_gia` (
  `id_doc_gia` int(11) NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(100) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` varchar(10) DEFAULT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_doc_gia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `doc_gia` (`id_doc_gia`, `ho_ten`, `ngay_sinh`, `gioi_tinh`, `so_dien_thoai`, `dia_chi`) VALUES
(1, 'Nguyễn Văn An', '2007-03-15', 'Nam', '0912345678', 'Hà Nội'),
(2, 'Trần Minh Anh', '2006-07-22', 'Nữ', '0987654321', 'Hà Nội'),
(3, 'Lê Hoàng Nam', '2007-01-10', 'Nam', '0901234567', 'Ninh Bình'),
(4, 'Phạm Thu Hà', '2006-11-05', 'Nữ', '0934567890', 'Bắc Ninh'),
(5, 'Đỗ Minh Quân', '2007-08-18', 'Nam', '0978123456', 'Thanh Hóa')
ON DUPLICATE KEY UPDATE `ho_ten` = VALUES(`ho_ten`);

-- --------------------------------------------------------
-- Bang `quyensach` (GIU NGUYEN)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `quyensach` (
  `id_sach` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `tac_gia` varchar(255) NOT NULL,
  `the_loai` varchar(255) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_sach`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quyensach` (`id_sach`, `name`, `img`, `tac_gia`, `the_loai`, `so_luong`) VALUES
(1, 'Có hai con mèo ngồi bên cửa sổ', 'Có hai con mèo ngồi bên cửa sổ.jpg', 'Nguyễn Nhật Ánh', 'Đồng thoại', 5),
(2, 'Hồ Điệp và Kình Ngư', 'ho-diep-va-kinh-ngu_17307_1.jpg', 'Tuế Kiến', 'Tiểu thuyết', 1),
(3, 'Làm bạn với bầu trời', '(Pdf) Làm bạn với bầu trời - Nguyễn Nhật Ánh.jpg', 'Nguyễn Nhật Ánh', 'Truyện dài', 5),
(4, 'Harry Potter và hòn đá phù thủy', 'harry-potter-1.jpg', 'J.K.Rowling', 'Fantasy', 1),
(5, 'Tuổi trẻ đáng giá bao nhiêu?', 'tuoi-tre-dang-gia-bao-nhieu-rosie-nguyen.jpg', 'Rosie Nguyễn', 'Kỹ năng sống', 2)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- --------------------------------------------------------
-- Bang `muon_tra` (GIU NGUYEN kem khoa ngoai)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `muon_tra` (
  `id_muon_tra` int(11) NOT NULL AUTO_INCREMENT,
  `id_sach` int(11) NOT NULL,
  `id_doc_gia` int(11) NOT NULL,
  `ngay_muon` date NOT NULL,
  `ngay_tra` date DEFAULT NULL,
  `trang_thai` varchar(30) DEFAULT 'Đang mượn',
  PRIMARY KEY (`id_muon_tra`),
  KEY `fk_muontra_sach` (`id_sach`),
  KEY `fk_muontra_docgia` (`id_doc_gia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `muon_tra` (`id_muon_tra`, `id_sach`, `id_doc_gia`, `ngay_muon`, `ngay_tra`, `trang_thai`) VALUES
(11, 1, 1, '2026-08-10', '2026-08-17', 'Đã trả'),
(12, 2, 4, '2026-08-12', NULL, 'Đang mượn'),
(13, 3, 2, '2026-08-13', '2026-08-16', 'Đã trả'),
(14, 4, 4, '2026-08-15', NULL, 'Đang mượn'),
(15, 5, 3, '2026-08-16', NULL, 'Đang mượn')
ON DUPLICATE KEY UPDATE `trang_thai` = VALUES(`trang_thai`);

-- Rang buoc khoa ngoai (tao neu chua co)
SET @fk1 := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'quanlythuvien' AND TABLE_NAME = 'muon_tra' AND CONSTRAINT_NAME = 'fk_muontra_sach');
SET @sql1 := IF(@fk1 = 0,
  'ALTER TABLE `muon_tra` ADD CONSTRAINT `fk_muontra_sach` FOREIGN KEY (`id_sach`) REFERENCES `quyensach` (`id_sach`)',
  'SELECT 1');
PREPARE st1 FROM @sql1; EXECUTE st1; DEALLOCATE PREPARE st1;

SET @fk2 := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'quanlythuvien' AND TABLE_NAME = 'muon_tra' AND CONSTRAINT_NAME = 'fk_muontra_docgia');
SET @sql2 := IF(@fk2 = 0,
  'ALTER TABLE `muon_tra` ADD CONSTRAINT `fk_muontra_docgia` FOREIGN KEY (`id_doc_gia`) REFERENCES `doc_gia` (`id_doc_gia`)',
  'SELECT 1');
PREPARE st2 FROM @sql2; EXECUTE st2; DEALLOCATE PREPARE st2;

-- --------------------------------------------------------
-- Bang `users` (bang TAI KHOAN moi — da duoc chu nhan dong y tao)
-- role = 'admin' : full quyen | role = 'user' : doc gia chi xem
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `id_doc_gia` int(11) DEFAULT NULL COMMENT 'Lien ket toi ho so doc gia (doc_gia.id_doc_gia). NULL = chua lien ket.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_docgia` (`id_doc_gia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Khoa ngoai users -> doc_gia (tao neu chua co)
SET @fk3 := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'quanlythuvien' AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_docgia');
SET @sql3 := IF(@fk3 = 0,
  'ALTER TABLE `users` ADD CONSTRAINT `fk_users_docgia` FOREIGN KEY (`id_doc_gia`) REFERENCES `doc_gia` (`id_doc_gia`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE st3 FROM @sql3; EXECUTE st3; DEALLOCATE PREPARE st3;

-- Tai khoan admin mac dinh: admin / admin123 (mat khau da bam bang password_hash)
INSERT IGNORE INTO `users` (`ho_ten`, `username`, `email`, `password`, `role`) VALUES
('Quản trị viên', 'admin', 'admin@thuvien.local',
 '$2y$10$htOR6zuSlVGRvkkiTXqfe.XIwn4wAo6YS2iHothwDj9.tlS86xe2G',
 'admin');

COMMIT;
